<?php

namespace App\Engines\Sales\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Identity\Models\Admin;
use App\Engines\Sales\Events\QuotationAccepted;
use App\Engines\Sales\Events\QuotationSent;
use App\Engines\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuotationWorkflowService
{
    public function __construct(private readonly AuditLogger $audit, private readonly DocumentNumberService $numbers)
    {
    }

    /**
     * Admin melengkapkan item & tempoh sah, kemudian menghantar kepada pelanggan. Selepas SENT, harga dikunci.
     *
     * @param list<array{description: string, quantity: int|string, unit_price: string|float}> $items
     */
    public function send(?Admin $admin, Quotation $quotation, array $items, string $validUntil, int $estimatedWeeks = 4, string $template = 'website'): Quotation
    {
        return DB::transaction(function () use ($admin, $quotation, $items, $validUntil, $estimatedWeeks, $template): Quotation {
            $quotation = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);
            if (! in_array($quotation->status, [Quotation::STATUS_DRAFT, Quotation::STATUS_REVIEW_REQUIRED], true) || $quotation->invalidated_at) {
                throw ValidationException::withMessages(['quotation' => ['Quotation ini tidak lagi boleh diubah atau dihantar.']]);
            }

            $lines = [];
            $totalCents = 0;
            foreach ($items as $item) {
                $qty = (int) $item['quantity'];
                $unit = (int) round(((float) $item['unit_price']) * 100);
                $lineCents = $qty * $unit;
                $totalCents += $lineCents;
                $lines[] = ['description' => trim($item['description']), 'quantity' => $qty, 'unit_price' => $this->money($unit), 'line_total' => $this->money($lineCents)];
            }

            $snapshot = $quotation->price_snapshot;
            $snapshot['items'] = $lines;
            $snapshot['project_template'] = $template;
            unset($snapshot['status_reason']);

            // Mod Billplz (sandbox/production) semasa quotation dihantar mengunci keseluruhan rantaian
            // (slot, order, projek) kepada mod yang sama — rekod ujian tidak pernah bercampur dengan production.
            $isSandbox = $quotation->is_sandbox || BillingSetting::current()->isSandbox();

            $quotation->forceFill([
                'is_sandbox' => $isSandbox,
                'number' => $quotation->number ?? $this->numbers->next('QT', $isSandbox),
                'status' => Quotation::STATUS_SENT,
                'price_snapshot' => $snapshot,
                'terms_snapshot' => $quotation->terms_snapshot ?? config('sales_terms'),
                'total_amount' => $this->money($totalCents),
                'estimated_weeks' => $estimatedWeeks,
                'valid_until' => $validUntil.' 23:59:59',
                'sent_at' => now(),
            ])->save();

            $this->audit->record('QUOTATION_SENT', $admin, $quotation, null, ['number' => $quotation->number, 'total' => $quotation->total_amount, 'valid_until' => $validUntil]);
            QuotationSent::dispatch($quotation);

            return $quotation;
        });
    }

    public function markViewed(Quotation $quotation, User $customer): void
    {
        $this->assertOwner($quotation, $customer);
        if ($quotation->status === Quotation::STATUS_SENT) {
            Quotation::query()->whereKey($quotation->id)->where('status', Quotation::STATUS_SENT)
                ->update(['status' => Quotation::STATUS_VIEWED, 'viewed_at' => now(), 'updated_at' => now()]);
        }
    }

    /** Pelanggan menerima quotation. Idempotent: penerimaan berulang tidak mencipta invois deposit kedua. */
    public function accept(Quotation $quotation, User $customer, string $ip, string $userAgent): Quotation
    {
        $this->assertOwner($quotation, $customer);

        $fresh = $quotation->fresh();
        if ($fresh->status !== Quotation::STATUS_ACCEPTED && $fresh->isExpired()) {
            Quotation::query()->whereKey($fresh->id)->whereIn('status', [Quotation::STATUS_SENT, Quotation::STATUS_VIEWED])
                ->update(['status' => Quotation::STATUS_EXPIRED, 'updated_at' => now()]);
            throw ValidationException::withMessages(['quotation' => ['Tempoh sah quotation ini telah tamat. Sila hubungi kami untuk quotation baharu.']]);
        }

        return DB::transaction(function () use ($quotation, $customer, $ip, $userAgent): Quotation {
            $quotation = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($quotation->status === Quotation::STATUS_ACCEPTED) {
                return $quotation;
            }
            if ($quotation->isExpired() || ! in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_VIEWED], true) || (float) $quotation->total_amount <= 0) {
                throw ValidationException::withMessages(['quotation' => ['Quotation ini tidak boleh diterima.']]);
            }

            $quotation->forceFill([
                'status' => Quotation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'accepted_by_user_id' => $customer->id,
                'acceptance_metadata' => [
                    'terms_version' => $quotation->terms_snapshot['version'] ?? null,
                    'terms_approval_status' => $quotation->terms_snapshot['approval_status'] ?? null,
                    'total_amount' => $quotation->total_amount,
                    'ip_hash' => hash('sha256', $ip.'|'.config('app.key')),
                    'user_agent' => Str::limit($userAgent, 490, ''),
                ],
            ])->save();

            $this->audit->record('QUOTATION_ACCEPTED', $customer, $quotation, null, ['number' => $quotation->number, 'total' => $quotation->total_amount]);
            QuotationAccepted::dispatch($quotation);

            return $quotation;
        });
    }

    public function assertOwner(Quotation $quotation, User $customer): void
    {
        abort_unless($quotation->request()->value('customer_user_id') === $customer->id, 404);
    }

    private function money(int $cents): string { return number_format($cents / 100, 2, '.', ''); }
}
