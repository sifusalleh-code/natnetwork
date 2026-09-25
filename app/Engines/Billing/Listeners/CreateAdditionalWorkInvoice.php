<?php

namespace App\Engines\Billing\Listeners;

use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Sales\Events\ChangeRequestApproved;
use App\Engines\Sales\Models\ChangeRequest;

/** Billing: Additional Work diluluskan pelanggan → invois ADDITIONAL_CHARGE 100%, sekali sahaja. Quotation asal tidak diubah. */
class CreateAdditionalWorkInvoice
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function handle(ChangeRequestApproved $event): void
    {
        $cr = ChangeRequest::query()->lockForUpdate()->findOrFail($event->changeRequest->id);
        if ($cr->invoice_id || $cr->status !== ChangeRequest::APPROVED) {
            return;
        }

        $customer = $cr->customer()->firstOrFail();
        $invoice = $this->invoices->issue('ADDITIONAL_CHARGE', [
            'name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone, 'company' => $customer->company,
        ], [[
            'description' => 'Kerja tambahan '.$cr->number.' — '.$cr->title,
            'quantity' => 1,
            'unit_price' => $cr->amount,
        ]], $customer, null, 'ChangeRequest', $cr->id);

        $cr->forceFill(['invoice_id' => $invoice->id])->save();
    }
}
