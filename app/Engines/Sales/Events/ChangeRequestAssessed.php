<?php

namespace App\Engines\Sales\Events;

use App\Engines\Sales\Models\ChangeRequest;
use Illuminate\Foundation\Events\Dispatchable;

class ChangeRequestAssessed
{
    use Dispatchable;

    public function __construct(public readonly ChangeRequest $changeRequest)
    {
    }
}
