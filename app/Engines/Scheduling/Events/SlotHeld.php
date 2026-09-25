<?php

namespace App\Engines\Scheduling\Events;

use App\Engines\Scheduling\Models\SlotHold;
use Illuminate\Foundation\Events\Dispatchable;

class SlotHeld
{
    use Dispatchable;

    public function __construct(public readonly SlotHold $hold)
    {
    }
}
