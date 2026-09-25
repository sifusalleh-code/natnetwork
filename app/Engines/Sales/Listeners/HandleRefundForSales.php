<?php

namespace App\Engines\Sales\Listeners;

use App\Engines\Billing\Events\RefundRecorded;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Services\ChangeRequestService;

/** Sales: refund projek → order dibatalkan dan Change Request terbuka dibatalkan. */
class HandleRefundForSales
{
    public function __construct(private readonly ChangeRequestService $changes)
    {
    }

    public function handle(RefundRecorded $event): void
    {
        Order::query()->whereKey($event->project->order_id)->update(['status' => Order::CANCELLED, 'updated_at' => now()]);
        $this->changes->cancelOpenForProject($event->project->id);
    }
}
