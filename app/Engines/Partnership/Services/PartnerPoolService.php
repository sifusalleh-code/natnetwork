<?php

namespace App\Engines\Partnership\Services;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Refund;
use App\Engines\Communication\Services\NotificationService;
use App\Engines\Partnership\Models\PartnerEarning;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Engines\Partnership\Models\PartnerPoolEntry;
use App\Engines\Partnership\Models\PartnerSetting;
use Illuminate\Support\Facades\DB;

/**
 * Pool partner: setiap jualan pelanggan production yang dibayar penuh → pool_percent% daripada amaun dibayar
 * diagih serta-merta ikut nisbah modal aktif. Refund → pembalikan berkadar. Semua dalam sen, baki pembundaran
 * diagih mengikut kaedah largest remainder supaya jumlah tepat.
 */
class PartnerPoolService
{
    public function __construct(private readonly NotificationService $notify)
    {
    }

    public static function isSale(Invoice $invoice): bool
    {
        return ! $invoice->is_sandbox && $invoice->customer_user_id !== null && $invoice->type !== 'PARTNER_CAPITAL';
    }

    public function allocateForInvoice(Invoice $invoice): ?PartnerPoolEntry
    {
        if (! self::isSale($invoice) || $invoice->status !== Invoice::STATUS_PAID) {
            return null;
        }

        return DB::transaction(function () use ($invoice): ?PartnerPoolEntry {
            Invoice::query()->whereKey($invoice->id)->lockForUpdate()->first();
            $existing = PartnerPoolEntry::query()->where('invoice_id', $invoice->id)->where('type', PartnerPoolEntry::ALLOCATION)->first();
            if ($existing) {
                return $existing;
            }

            $percent = (float) PartnerSetting::current()->pool_percent;
            $saleCents = (int) round((float) $invoice->amount_paid * 100);
            $poolCents = (int) round($saleCents * $percent / 100);
            $capital = PartnerCapital::query()->where('status', PartnerCapital::ACTIVE)->where('is_sandbox', false)
                ->selectRaw('partner_id, SUM(amount) as capital')->groupBy('partner_id')->get()
                ->mapWithKeys(fn ($r) => [(int) $r->partner_id => (int) round((float) $r->capital * 100)]);
            $totalCapital = (int) $capital->sum();
            $shares = $totalCapital > 0 ? $this->split($poolCents, $capital->all()) : [];

            $entry = PartnerPoolEntry::query()->create([
                'type' => PartnerPoolEntry::ALLOCATION, 'invoice_id' => $invoice->id, 'refund_id' => null,
                'sale_amount' => $this->money($saleCents), 'pool_percent' => $percent, 'pool_amount' => $this->money($poolCents),
                'allocated_amount' => $this->money(array_sum($shares)), 'total_capital' => $this->money($totalCapital), 'created_at' => now(),
            ]);
            foreach ($shares as $partnerId => $cents) {
                if ($cents === 0) {
                    continue;
                }
                PartnerEarning::query()->create(['partner_pool_entry_id' => $entry->id, 'partner_id' => $partnerId, 'capital' => $this->money($capital[$partnerId]), 'amount' => $this->money($cents), 'created_at' => now()]);
                $this->notify->toPartner($partnerId, 'EARNING', 'Pulangan baharu RM '.$this->money($cents), 'Bahagian pool daripada jualan '.$invoice->number.'.', route('partner.returns'));
            }

            return $entry;
        });
    }

    /** Refund pelanggan → terbalikkan bahagian pool berkadar dengan agihan asal invois itu. Idempotent per refund. */
    public function reverseForRefund(Refund $refund): ?PartnerPoolEntry
    {
        return DB::transaction(function () use ($refund): ?PartnerPoolEntry {
            $original = PartnerPoolEntry::query()->where('invoice_id', $refund->invoice_id)->where('type', PartnerPoolEntry::ALLOCATION)->lockForUpdate()->first();
            if (! $original || PartnerPoolEntry::query()->where('refund_id', $refund->id)->exists()) {
                return null;
            }

            $saleCents = (int) round((float) $original->sale_amount * 100);
            $refundCents = min($saleCents, (int) round((float) $refund->amount * 100));
            $allocatedCents = (int) round((float) $original->allocated_amount * 100);
            $reverseCents = $saleCents > 0 ? (int) round($allocatedCents * $refundCents / $saleCents) : 0;
            $weights = $original->earnings()->get()->mapWithKeys(fn ($e) => [(int) $e->partner_id => (int) round((float) $e->amount * 100)])->all();
            $shares = $allocatedCents > 0 ? $this->split($reverseCents, $weights) : [];

            $entry = PartnerPoolEntry::query()->create([
                'type' => PartnerPoolEntry::REVERSAL, 'invoice_id' => $refund->invoice_id, 'refund_id' => $refund->id,
                'sale_amount' => $this->money(-$refundCents), 'pool_percent' => $original->pool_percent,
                'pool_amount' => $this->money(-(int) round($refundCents * (float) $original->pool_percent / 100)),
                'allocated_amount' => $this->money(-array_sum($shares)), 'total_capital' => $original->total_capital, 'created_at' => now(),
            ]);
            foreach ($shares as $partnerId => $cents) {
                if ($cents !== 0) {
                    PartnerEarning::query()->create(['partner_pool_entry_id' => $entry->id, 'partner_id' => $partnerId, 'capital' => '0.00', 'amount' => $this->money(-$cents), 'created_at' => now()]);
                }
            }

            return $entry;
        });
    }

    /**
     * Bahagi $totalCents ikut pemberat (largest remainder).
     *
     * @param array<int, int> $weights
     * @return array<int, int>
     */
    public function split(int $totalCents, array $weights): array
    {
        $sum = array_sum($weights);
        if ($sum <= 0 || $totalCents <= 0) {
            return array_map(fn () => 0, $weights);
        }
        $shares = [];
        $remainders = [];
        foreach ($weights as $id => $w) {
            $exact = $totalCents * $w / $sum;
            $shares[$id] = (int) floor($exact);
            $remainders[$id] = $exact - $shares[$id];
        }
        arsort($remainders);
        $left = $totalCents - array_sum($shares);
        foreach (array_keys($remainders) as $id) {
            if ($left-- <= 0) {
                break;
            }
            $shares[$id]++;
        }

        return $shares;
    }

    private function money(int $cents): string { return number_format($cents / 100, 2, '.', ''); }
}
