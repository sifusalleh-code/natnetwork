<?php

namespace App\Engines\Project\Listeners;

use App\Engines\Project\Services\ProjectService;
use App\Engines\Sales\Events\OrderConfirmed;

class CreateProjectOnOrderConfirmed
{
    public function __construct(private readonly ProjectService $projects)
    {
    }

    public function handle(OrderConfirmed $event): void
    {
        $this->projects->createFromOrder($event->order->loadMissing('slotHold'));
    }
}
