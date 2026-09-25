<?php

namespace App\Providers;

use App\Engines\Billing\Events\RefundRecorded;
use App\Engines\Partnership\Listeners\HandlePartnerBillingEvents;
use App\Engines\Billing\Listeners\CreateAdditionalWorkInvoice;
use App\Engines\Project\Listeners\HandleProjectEvents;
use App\Engines\Sales\Events\ChangeRequestApproved;
use App\Engines\Sales\Listeners\HandleRefundForSales;
use App\Engines\Scheduling\Events\SlotRescheduled;
use App\Engines\Scheduling\Listeners\HandleScheduleEvents;
use App\Engines\Affiliate\Listeners\HandleBillingEvents;
use App\Engines\Billing\Events\InvoiceIssued;
use App\Engines\Billing\Events\ProjectPaymentCompleted;
use App\Engines\Billing\Listeners\CreateDepositInvoice;
use App\Engines\Billing\Events\InvoicePaid;
use App\Engines\Billing\Listeners\CreateFinalInvoice;
use App\Engines\Communication\Listeners\SendPortalNotifications;
use App\Engines\Project\Events\ProjectReachedReview;
use App\Engines\Project\Listeners\CreateProjectOnOrderConfirmed;
use App\Engines\Sales\Events\OrderConfirmed;
use App\Engines\Sales\Listeners\ConfirmOrderOnDepositPaid;
use App\Engines\Scheduling\Events\SlotHeld;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(\App\Engines\Cms\Services\SeoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(InvoiceIssued::class, [HandleBillingEvents::class, 'issued']);
        Event::listen(ProjectPaymentCompleted::class, [HandleBillingEvents::class, 'projectCompleted']);
        Event::listen(SlotHeld::class, [CreateDepositInvoice::class, 'handle']);
        Event::listen(InvoicePaid::class, [ConfirmOrderOnDepositPaid::class, 'handle']);
        Event::listen(OrderConfirmed::class, [CreateProjectOnOrderConfirmed::class, 'handle']);
        Event::listen(ProjectReachedReview::class, [CreateFinalInvoice::class, 'handle']);
        Event::listen(RefundRecorded::class, [HandleProjectEvents::class, 'refunded']);
        Event::listen(RefundRecorded::class, [HandleRefundForSales::class, 'handle']);
        Event::listen(RefundRecorded::class, [HandleBillingEvents::class, 'refunded']);
        Event::listen(SlotRescheduled::class, [HandleProjectEvents::class, 'rescheduled']);
        Event::listen(ChangeRequestApproved::class, [CreateAdditionalWorkInvoice::class, 'handle']);
        Event::listen(ChangeRequestApproved::class, [HandleScheduleEvents::class, 'changeApproved']);
        Event::listen(InvoicePaid::class, [HandlePartnerBillingEvents::class, 'paid']);
        Event::listen(RefundRecorded::class, [HandlePartnerBillingEvents::class, 'refunded']);
        Event::subscribe(SendPortalNotifications::class);
    }
}
