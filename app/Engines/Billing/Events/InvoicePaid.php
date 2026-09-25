<?php

namespace App\Engines\Billing\Events;

use App\Engines\Billing\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/** Dipancarkan oleh Billing apabila invois dibayar penuh (disahkan melalui callback Billplz di pelayan). */
class InvoicePaid
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice)
    {
    }
}
