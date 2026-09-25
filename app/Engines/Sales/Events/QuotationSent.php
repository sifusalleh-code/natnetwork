<?php

namespace App\Engines\Sales\Events;

use App\Engines\Sales\Models\Quotation;
use Illuminate\Foundation\Events\Dispatchable;

class QuotationSent
{
    use Dispatchable;

    public function __construct(public readonly Quotation $quotation)
    {
    }
}
