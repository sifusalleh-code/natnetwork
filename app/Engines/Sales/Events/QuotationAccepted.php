<?php

namespace App\Engines\Sales\Events;

use App\Engines\Sales\Models\Quotation;
use Illuminate\Foundation\Events\Dispatchable;

/** Dipancarkan oleh Sales selepas pelanggan menerima quotation. Billing mencipta invois deposit. */
class QuotationAccepted
{
    use Dispatchable;

    public function __construct(public readonly Quotation $quotation)
    {
    }
}
