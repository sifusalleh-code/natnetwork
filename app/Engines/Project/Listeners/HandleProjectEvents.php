<?php

namespace App\Engines\Project\Listeners;

use App\Engines\Billing\Events\RefundRecorded;
use App\Engines\Project\Models\Project;
use App\Engines\Project\Services\ProjectService;
use App\Engines\Scheduling\Events\SlotRescheduled;

class HandleProjectEvents
{
    public function __construct(private readonly ProjectService $projects)
    {
    }

    public function refunded(RefundRecorded $event): void
    {
        $this->projects->cancelForRefund($event->admin, $event->project, $event->reason);
    }

    public function rescheduled(SlotRescheduled $event): void
    {
        Project::query()->where('quotation_id', $event->hold->quotation_id)->whereNull('started_at')
            ->update(['planned_start_date' => $event->hold->start_date->toDateString(), 'updated_at' => now()]);
    }
}
