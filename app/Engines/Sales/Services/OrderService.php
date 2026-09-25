<?php

namespace App\Engines\Sales\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Sales\Events\OrderConfirmed;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Services\SchedulingService;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private readonly SchedulingService $scheduling,
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * ORDER CONFIRMED hanya bila deposit kumulatif ≥ peratus deposit dan disahkan pelayan, DAN slot telah
     * diluluskan/ditempah (Keputusan Owner 25 Sep 2026, #6). Idempotent.
     * Bayaran sah tanpa slot yang boleh ditempah tidak pernah dibatalkan secara senyap — ia direkod sebagai
     * scheduling exception untuk tindakan admin (lihat AGENTS/MS §11).
     */
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
        if (! $hold) {
            Log::error('Scheduling exception: deposit disahkan tanpa slot untuk order confirmed', ['quotation_id' => $quotation->id]);
            $this->audit->record('SCHEDULING_EXCEPTION_NO_SLOT', null, $quotation, null, [
                'quotation_id' => $quotation->id, 'paid_deposit_cents' => $paidDepositCents,
            ], 'Bayaran deposit disahkan tetapi tiada slot hold untuk quotation ini. Order TIDAK disahkan secara automatik — perlu tindakan admin.');

            return null;
        }

        $order = Order::query()->create([
            'number' => $this->numbers->next('ORD', (bool) $quotation->is_sandbox),
            'quotation_id' => $quotation->id,
            'is_sandbox' => (bool) $quotation->is_sandbox,
            'customer_user_id' => $quotation->request()->value('customer_user_id'),
            'slot_hold_id' => $hold->id,
            'status' => Order::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        OrderConfirmed::dispatch($order);

        return $order;
    }
}
