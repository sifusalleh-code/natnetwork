<?php

namespace App\Engines\Project\Events;

use App\Engines\Project\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectStatusChanged
{
    use Dispatchable;

    public function __construct(public readonly Project $project, public readonly ?string $from, public readonly string $to, public readonly ?string $reason = null)
    {
    }
}
