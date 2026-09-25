<?php

namespace App\Engines\Billing\Events;

use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

/** Billing merekod refund projek sebelum START PROJECT. Engine lain membatalkan projek, order, CR dan komisyen. */
class RefundRecorded
{
    use Dispatchable;

    /**
     * @param list<int> $refundIds
     * @param list<int> $projectInvoiceIds semua invois projek (termasuk yang di-void)
     */
    public function __construct(
        public readonly Admin $admin,
        public readonly Project $project,
        public readonly array $refundIds,
        public readonly array $projectInvoiceIds,
        public readonly string $reason,
        public readonly int $totalCents,
    ) {
    }
}
