<?php

namespace App\Engines\Billing\Services;

use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Pricing\Models\Addon;
use App\Engines\Sales\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Billing: pilihan cara bayar bagi quotation yang diterima — 50% deposit (baki dijana pada 80% progress)
 * atau 100% bayaran penuh (tiada invois baki + ganjaran yang ditetapkan admin). Pilihan dikunci selepas invois dikeluarkan.
 */
class PaymentPlanService
{
    public function depositPercent(): int
    {
        return (int) config('billing.deposit_percent', 50);
    }

    /** Ganjaran bayar penuh semasa (daripada tetapan admin) untuk jumlah quotation ini. */
    public function currentReward(Quotation $quotation): ?array
    {
        $s = BillingSetting::current();
        if (! $s->full_payment_reward_enabled || ! $s->full_payment_reward_type) {
            return null;
        }
        $totalCents = $this->totalCents($quotation);
        $value = (float) $s->full_payment_reward_value;

        return match ($s->full_payment_reward_type) {
            'percent' => $value > 0 ? $this->discountReward('percent', $value, min($totalCents, (int) round($totalCents * min($value, 100) / 100)), 'Diskaun '.rtrim(rtrim(number_format($value, 2), '0'), '.').'%') : null,
            'fixed' => $value > 0 ? $this->discountReward('fixed', $value, min($totalCents, (int) round($value * 100)), 'Diskaun RM'.number_format($value, 2)) : null,
            'addon' => ($addon = Addon::query()->whereKey($s->full_payment_reward_addon_id)->first())
                ? ['type' => 'addon', 'value' => null, 'discount_cents' => 0, 'label' => 'Percuma: '.$addon->name, 'addon_name' => $addon->name, 'addon_value' => $addon->price_label]
                : null,
            default => null,
        };
    }

    /** @return array{deposit: array, full: array, locked: ?QuotationPaymentPlan} */
    public function options(Quotation $quotation): array
    {
        $total = $this->totalCents($quotation);
        $percent = $this->depositPercent();
        $plan = $this->planFor($quotation);
        $reward = $plan?->plan === QuotationPaymentPlan::FULL ? $this->rewardFromPlan($plan) : $this->currentReward($quotation);
        $discount = $reward['discount_cents'] ?? 0;

        return [
            'deposit' => ['percent' => $percent, 'pay_cents' => (int) round($total * $percent / 100), 'balance_cents' => $total - (int) round($total * $percent / 100)],
            'full' => ['percent' => 100, 'gross_cents' => $total, 'discount_cents' => $discount, 'pay_cents' => $total - $discount, 'reward' => $reward],
            'locked' => $plan?->invoice_id ? $plan : null,
            'selected' => $plan?->plan,
        ];
    }

    /** Simpan pilihan pelanggan. Selepas invois dikeluarkan, pilihan tidak boleh ditukar (reset Start Project diperlukan). */
    public function select(Quotation $quotation, string $plan): QuotationPaymentPlan
    {
        if (! in_array($plan, QuotationPaymentPlan::PLANS, true)) {
            throw ValidationException::withMessages(['plan' => ['Sila pilih cara bayaran.']]);
        }

        return DB::transaction(function () use ($quotation, $plan): QuotationPaymentPlan {
            $existing = QuotationPaymentPlan::query()->where('quotation_id', $quotation->id)->lockForUpdate()->first();
            if ($existing?->invoice_id) {
                return $existing; // bil sama diteruskan
            }
            $reward = $plan === QuotationPaymentPlan::FULL ? $this->currentReward($quotation) : null;
            $data = [
                'plan' => $plan,
                'percent' => $plan === QuotationPaymentPlan::FULL ? 100 : $this->depositPercent(),
                'reward_snapshot' => $reward ? array_diff_key($reward, ['discount_cents' => true]) : null,
                'discount_amount' => number_format(($reward['discount_cents'] ?? 0) / 100, 2, '.', ''),
                'selected_at' => now(),
            ];

            return $existing ? tap($existing)->update($data) : QuotationPaymentPlan::query()->create($data + ['quotation_id' => $quotation->id]);
        });
    }

    public function planFor(Quotation $quotation): ?QuotationPaymentPlan
    {
        return QuotationPaymentPlan::query()->where('quotation_id', $quotation->id)->first();
    }

    /** Diskaun bayar penuh yang telah dikunci pada invois (0 jika tiada). */
    public static function lockedDiscountCents(int $quotationId): int
    {
        $plan = QuotationPaymentPlan::query()->where('quotation_id', $quotationId)->whereNotNull('invoice_id')->first();

        return $plan?->plan === QuotationPaymentPlan::FULL ? $plan->discountCents() : 0;
    }

    private function totalCents(Quotation $quotation): int
    {
        return (int) round((float) $quotation->total_amount * 100);
    }

    private function discountReward(string $type, float $value, int $cents, string $label): array
    {
        return ['type' => $type, 'value' => $value, 'discount_cents' => $cents, 'label' => $label, 'addon_name' => null, 'addon_value' => null];
    }

    private function rewardFromPlan(QuotationPaymentPlan $plan): ?array
    {
        return $plan->reward_snapshot ? $plan->reward_snapshot + ['discount_cents' => $plan->discountCents()] : null;
    }
}
