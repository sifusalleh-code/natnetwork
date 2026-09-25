<?php

namespace App\Engines\Scheduling\Listeners;

use App\Engines\Sales\Events\ChangeRequestApproved;
use App\Engines\Scheduling\Services\SchedulingService;

class HandleScheduleEvents
{
    public function __construct(private readonly SchedulingService $scheduling)
    {
    }

    public function changeApproved(ChangeRequestApproved $event): void
    {
        $this->scheduling->extendForQuotation($event->changeRequest->quotation_id, $event->changeRequest->extra_weeks);
    }
}
