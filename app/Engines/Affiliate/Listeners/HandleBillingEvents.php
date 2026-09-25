<?php

namespace App\Engines\Affiliate\Listeners;

use App\Engines\Affiliate\Services\AffiliateCommissionService;
use App\Engines\Billing\Events\InvoiceIssued;
use App\Engines\Billing\Events\ProjectPaymentCompleted;
use App\Engines\Billing\Events\RefundRecorded;

class HandleBillingEvents
{
    public function __construct(private readonly AffiliateCommissionService $commissions)
    {
    }

    public function issued(InvoiceIssued $event): void
    {
        $this->commissions->recordForInvoice($event->invoice);
    }

    public function refunded(RefundRecorded $event): void
    {
        $this->commissions->cancelForInvoices($event->projectInvoiceIds);
    }

    public function projectCompleted(ProjectPaymentCompleted $event): void
    {
        $this->commissions->releaseForInvoices($event->invoiceIds);
    }
}
