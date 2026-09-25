<?php

namespace App\Engines\Billing\Events;

use App\Engines\Sales\Models\Quotation;
use Illuminate\Foundation\Events\Dispatchable;

/** Dipancarkan bila admin mengesahkan semua bayaran projek (quotation) telah selesai. */
class ProjectPaymentCompleted
{
    use Dispatchable;

    /** @param list<int> $invoiceIds */
    public function __construct(public readonly Quotation $quotation, public readonly array $invoiceIds)
    {
    }
}
