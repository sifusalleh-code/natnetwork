<?php

namespace App\Engines\Sales\Listeners;

use App\Engines\Billing\Events\InvoicePaid;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Sales\Services\OrderService;

class ConfirmOrderOnDepositPaid
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        if ($invoice->type !== 'DEPOSIT' || $invoice->source_type !== 'Quotation') {
            return;
        }

        $quotation = Quotation::query()->find($invoice->source_id);
        if ($quotation) {
            $this->orders->confirmIfDepositMet($quotation);
        }
    }
}
