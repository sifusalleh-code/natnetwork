<?php

namespace App\Engines\Project\Events;

use App\Engines\Project\Models\Project;
use App\Engines\Project\Models\ProjectMilestone;
use Illuminate\Foundation\Events\Dispatchable;

class MilestoneCompleted
{
    use Dispatchable;

    public function __construct(public readonly Project $project, public readonly ProjectMilestone $milestone)
    {
    }
}
