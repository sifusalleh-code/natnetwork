<?php

namespace App\Engines\Sales\Events;

use App\Engines\Sales\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderConfirmed
{
    use Dispatchable;

    public function __construct(public readonly Order $order)
    {
    }
}
