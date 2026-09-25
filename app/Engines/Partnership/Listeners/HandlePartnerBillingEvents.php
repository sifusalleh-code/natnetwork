<?php

namespace App\Engines\Partnership\Listeners;

use App\Engines\Billing\Events\InvoicePaid;
use App\Engines\Billing\Events\RefundRecorded;
use App\Engines\Billing\Models\Refund;
use App\Engines\Partnership\Services\PartnerPoolService;
use App\Engines\Partnership\Services\PartnerService;

class HandlePartnerBillingEvents
{
    public function __construct(private readonly PartnerService $partners, private readonly PartnerPoolService $pool)
    {
    }

    public function paid(InvoicePaid $event): void
    {
        $this->partners->activateForInvoice($event->invoice);
        $this->pool->allocateForInvoice($event->invoice);
    }

    public function refunded(RefundRecorded $event): void
    {
        foreach (Refund::query()->whereIn('id', $event->refundIds)->get() as $refund) {
            $this->pool->reverseForRefund($refund);
        }
    }
}
