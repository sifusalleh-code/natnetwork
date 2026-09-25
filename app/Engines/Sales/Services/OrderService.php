<?php

namespace App\Engines\Sales\Services;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Sales\Events\OrderConfirmed;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Services\SchedulingService;

class OrderService
{
    public function __construct(private readonly SchedulingService $scheduling, private readonly DocumentNumberService $numbers)
    {
    }

    /** ORDER CONFIRMED hanya bila deposit kumulatif ≥ peratus deposit dan disahkan pelayan. Idempotent. */
    public function confirmIfDepositMet(Quotation $quotation): ?Order
    {
        $existing = Order::query()->where('quotation_id', $quotation->id)->first();
        if ($existing) {
            return $existing;
        }

        $paidDepositCents = (int) Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)
            ->where('type', 'DEPOSIT')->get()->sum(fn ($i) => (int) round((float) $i->amount_paid * 100));
        $requiredCents = (int) round((float) $quotation->total_amount * 100 * (int) config('billing.deposit_percent', 50) / 100);
        if ($paidDepositCents < $requiredCents) {
            return null;
        }

        $hold = $this->scheduling->reserveForQuotation($quotation);
        $order = Order::query()->create([
            'number' => $this->numbers->next('ORD', false),
            'quotation_id' => $quotation->id,
            'customer_user_id' => $quotation->request()->value('customer_user_id'),
            'slot_hold_id' => $hold?->id,
            'status' => Order::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        OrderConfirmed::dispatch($order);

        return $order;
    }
}
