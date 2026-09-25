<?php

namespace App\Engines\Scheduling\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Identity\Models\Admin;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Events\SlotHeld;
use App\Engines\Scheduling\Events\SlotRescheduled;
use App\Engines\Scheduling\Models\SchedulingSetting;
use App\Engines\Scheduling\Models\SlotHold;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Scheduling: pemilik tunggal kapasiti, slot, hold dan reservation. */
class SchedulingService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function expireStale(): int
    {
        return SlotHold::query()->where('status', SlotHold::HELD)->where('expires_at', '<=', now())
            ->update(['status' => SlotHold::EXPIRED, 'released_at' => now(), 'updated_at' => now()]);
    }

    public function weeksFor(Quotation $quotation): int
    {
        return max(1, (int) ($quotation->estimated_weeks ?: 4));
    }

    /**
     * Senarai minggu mula (Isnin) dengan status tersedia untuk tempoh projek ini.
     *
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable, available: bool}>
     */
    public function availableStarts(Quotation $quotation): Collection
    {
        $this->expireStale();
        $settings = SchedulingSetting::current();
        $weeks = $this->weeksFor($quotation);
        $first = CarbonImmutable::now()->next(CarbonImmutable::MONDAY)->startOfDay();

        return collect(range(0, $settings->weeks_ahead - 1))->map(function (int $i) use ($first, $weeks, $quotation, $settings): array {
            $start = $first->addWeeks($i);

            return ['start' => $start, 'end' => $start->addWeeks($weeks)->subDay(), 'available' => $this->fits($start, $weeks, $settings->max_active_projects, $quotation->id)];
        });
    }

    /** Muat untuk setiap minggu dalam tempoh? */
    public function fits(CarbonImmutable $start, int $weeks, int $capacity, ?int $excludeQuotationId = null): bool
    {
        $end = $start->addWeeks($weeks);
        $holds = SlotHold::query()->occupying()
            ->when($excludeQuotationId, fn ($q) => $q->where('quotation_id', '!=', $excludeQuotationId))
            ->where('start_date', '<', $end->toDateString())->get()
            ->filter(fn (SlotHold $h) => $h->endDate()->greaterThan($start));

        for ($w = $start; $w->lessThan($end); $w = $w->addWeek()) {
            $load = $holds->filter(fn (SlotHold $h) => $h->start_date->lessThanOrEqualTo($w) && $h->endDate()->greaterThan($w))->count();
            if ($load >= $capacity) {
                return false;
            }
        }

        return true;
    }

    public function currentHold(Quotation $quotation): ?SlotHold
    {
        $this->expireStale();

        return SlotHold::query()->where('quotation_id', $quotation->id)->whereIn('status', [SlotHold::HELD, SlotHold::RESERVED])->latest('id')->first();
    }

    /** Pelanggan pilih minggu mula → HOLD sementara. Hold lama (belum reserved) digantikan. */
    public function hold(Quotation $quotation, string $startDate): SlotHold
    {
        $hold = DB::transaction(function () use ($quotation, $startDate): SlotHold {
            $this->expireStale();
            $quotation = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);
            if ($quotation->status !== Quotation::STATUS_ACCEPTED) {
                throw ValidationException::withMessages(['slot' => ['Quotation perlu diterima sebelum memilih slot.']]);
            }
            if (SlotHold::query()->where('quotation_id', $quotation->id)->where('status', SlotHold::RESERVED)->exists()) {
                throw ValidationException::withMessages(['slot' => ['Slot projek ini telah ditempah.']]);
            }

            $start = CarbonImmutable::parse($startDate)->startOfDay();
            $valid = $this->availableStarts($quotation)->first(fn ($s) => $s['start']->equalTo($start));
            // Kunci semua hold aktif untuk menghalang tempahan serentak melebihi kapasiti.
            SlotHold::query()->occupying()->lockForUpdate()->get();
            if (! $valid || ! $this->fits($start, $this->weeksFor($quotation), SchedulingSetting::current()->max_active_projects, $quotation->id)) {
                throw ValidationException::withMessages(['slot' => ['Slot ini tidak lagi tersedia. Sila pilih minggu lain.']]);
            }

            SlotHold::query()->where('quotation_id', $quotation->id)->where('status', SlotHold::HELD)
                ->update(['status' => SlotHold::RELEASED, 'released_at' => now(), 'updated_at' => now()]);

            return SlotHold::query()->create([
                'quotation_id' => $quotation->id,
                'start_date' => $start->toDateString(),
                'weeks' => $this->weeksFor($quotation),
                'status' => SlotHold::HELD,
                'expires_at' => now()->addMinutes(SchedulingSetting::current()->hold_minutes),
            ]);
        });

        SlotHeld::dispatch($hold);

        return $hold;
    }

    /**
     * Deposit disahkan → hold menjadi RESERVED. Tidak pernah membatalkan bayaran:
     * jika hold tamat & slot tidak lagi muat, tetap reserved dengan tanda konflik untuk tindakan admin.
     */
    public function reserveForQuotation(Quotation $quotation): ?SlotHold
    {
        $existing = SlotHold::query()->where('quotation_id', $quotation->id)->where('status', SlotHold::RESERVED)->first();
        if ($existing) {
            return $existing;
        }

        $hold = SlotHold::query()->where('quotation_id', $quotation->id)->whereIn('status', [SlotHold::HELD, SlotHold::EXPIRED, SlotHold::RELEASED])->latest('id')->lockForUpdate()->first();
        if (! $hold) {
            return null;
        }

        $conflict = ! $hold->isActiveHold()
            && ! $this->fits(CarbonImmutable::parse($hold->start_date), $hold->weeks, SchedulingSetting::current()->max_active_projects, $quotation->id);

        $hold->forceFill(['status' => SlotHold::RESERVED, 'reserved_at' => now(), 'expires_at' => null, 'conflict' => $conflict])->save();

        return $hold;
    }

    /**
     * Admin jadual semula slot yang telah ditempah. Minggu penuh dibenarkan hanya dengan pengesahan override.
     * Tanda KONFLIK dibersihkan kerana admin telah membuat keputusan jadual.
     */
    public function reschedule(Admin $admin, Quotation $quotation, string $startDate, int $weeks, bool $overrideCapacity, string $reason): SlotHold
    {
        $result = DB::transaction(function () use ($admin, $quotation, $startDate, $weeks, $overrideCapacity, $reason): array {
            $this->expireStale();
            $hold = SlotHold::query()->where('quotation_id', $quotation->id)->where('status', SlotHold::RESERVED)->lockForUpdate()->first();
            if (! $hold) {
                throw ValidationException::withMessages(['slot' => ['Tiada slot yang telah ditempah untuk projek ini.']]);
            }
            $start = CarbonImmutable::parse($startDate)->startOfDay();
            if (! $start->isMonday()) {
                throw ValidationException::withMessages(['start_date' => ['Minggu mula mesti hari Isnin.']]);
            }
            SlotHold::query()->occupying()->lockForUpdate()->get();
            $fits = $this->fits($start, $weeks, SchedulingSetting::current()->max_active_projects, $quotation->id);
            if (! $fits && ! $overrideCapacity) {
                throw ValidationException::withMessages(['override' => ['Minggu ini melebihi kapasiti. Tandakan pengesahan override untuk meneruskan.']]);
            }

            $previous = ['start_date' => $hold->start_date->toDateString(), 'weeks' => $hold->weeks, 'conflict' => $hold->conflict];
            $hold->forceFill(['start_date' => $start->toDateString(), 'weeks' => $weeks, 'conflict' => false])->save();
            $this->audit->record('SLOT_RESCHEDULED', $admin, $hold, $previous, ['start_date' => $start->toDateString(), 'weeks' => $weeks, 'over_capacity' => ! $fits], $reason);

            return [$hold, $previous['start_date']];
        });

        SlotRescheduled::dispatch($result[0], $result[1], $reason);

        return $result[0];
    }

    /** Additional Work diluluskan → tempoh slot dipanjangkan. Tidak menyekat: jika melebihi kapasiti, tandakan KONFLIK. */
    public function extendForQuotation(int $quotationId, int $extraWeeks): void
    {
        if ($extraWeeks <= 0) {
            return;
        }
        $hold = SlotHold::query()->where('quotation_id', $quotationId)->where('status', SlotHold::RESERVED)->lockForUpdate()->first();
        if (! $hold) {
            return;
        }
        $weeks = $hold->weeks + $extraWeeks;
        $fits = $this->fits(CarbonImmutable::parse($hold->start_date), $weeks, SchedulingSetting::current()->max_active_projects, $quotationId);
        $hold->forceFill(['weeks' => $weeks, 'conflict' => $hold->conflict || ! $fits])->save();
    }

    public function releaseForQuotation(int $quotationId): void
    {
        SlotHold::query()->where('quotation_id', $quotationId)->whereIn('status', [SlotHold::HELD, SlotHold::RESERVED])
            ->update(['status' => SlotHold::RELEASED, 'released_at' => now(), 'updated_at' => now()]);
    }
}
