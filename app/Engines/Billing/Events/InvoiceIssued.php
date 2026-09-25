<?php

namespace App\Engines\Billing\Events;

use App\Engines\Billing\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/** Dipancarkan oleh Billing selepas invois dicipta. */
class InvoiceIssued
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice)
    {
    }
}
