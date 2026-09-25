<?php

namespace App\Engines\Project\Events;

use App\Engines\Project\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectReachedReview
{
    use Dispatchable;

    public function __construct(public readonly Project $project)
    {
    }
}
