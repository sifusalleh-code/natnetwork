<?php

namespace App\Engines\Scheduling\Events;

use App\Engines\Scheduling\Models\SlotHold;
use Illuminate\Foundation\Events\Dispatchable;

class SlotRescheduled
{
    use Dispatchable;

    public function __construct(public readonly SlotHold $hold, public readonly string $previousStart, public readonly string $reason)
    {
    }
}
