<?php

namespace App\Engines\Billing\Listeners;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Scheduling\Events\SlotHeld;
use App\Models\User;

/** Billing: slot dipegang (selepas quotation diterima) → invois pertama (deposit 50% atau bayaran penuh 100%) dicipta sekali sahaja. */
class CreateDepositInvoice
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function handle(SlotHeld $event): void
    {
        $quotation = $event->hold->quotation()->firstOrFail()->loadMissing('request.customer');

        if (Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->where('type', 'DEPOSIT')->exists()) {
            return;
        }

        // Cara bayar pilihan pelanggan (50% deposit atau 100% penuh). Tiada pilihan → deposit default.
        $plan = QuotationPaymentPlan::query()->where('quotation_id', $quotation->id)->first();
        $totalCents = (int) round((float) $quotation->total_amount * 100);
        if ($plan?->plan === QuotationPaymentPlan::FULL) {
            $reward = $plan->reward_snapshot['label'] ?? null;
            $amountCents = $totalCents - $plan->discountCents();
            $description = "Bayaran penuh 100% — Quotation {$quotation->number}".($reward ? " ({$reward})" : '');
        } else {
            $percent = (int) config('billing.deposit_percent', 50);
            $amountCents = (int) round($totalCents * $percent / 100);
            $description = "Deposit {$percent}% — Quotation {$quotation->number}";
        }
        /** @var User $customer */
        $customer = $quotation->request->customer;

        $invoice = $this->invoices->issue('DEPOSIT', [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $customer->company,
        ], [[
            'description' => $description,
            'quantity' => 1,
            'unit_price' => number_format($amountCents / 100, 2, '.', ''),
        ]], $customer, null, 'Quotation', $quotation->id);

        $plan?->forceFill(['invoice_id' => $invoice->id])->save();
    }
}
