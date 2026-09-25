<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Affiliate\Models\AffiliateRateVersion;
use App\Engines\Billing\Models\Invoice;
use Illuminate\Support\Facades\DB;

class AffiliateCommissionService
{
    public function __construct(private readonly CommissionCalculator $calculator)
    {
    }

    /**
     * Pembelian = satu invois production. Komisyen PENDING dicipta semasa invois dikeluarkan.
     * Invois sandbox, invois tanpa pelanggan, atau pelanggan tanpa affiliate diabaikan. Idempotent.
     */
    public function recordForInvoice(Invoice $invoice): ?AffiliateCommission
    {
        if ($invoice->is_sandbox || ! $invoice->customer_user_id) {
            return null;
        }

        $link = AffiliateClientLink::query()->where('customer_user_id', $invoice->customer_user_id)->first();
        if (! $link) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $link): AffiliateCommission {
            // Kunci rekod komisyen pelanggan ini supaya jumlah terkumpul dikira berurutan.
            $existing = AffiliateCommission::query()->where('customer_user_id', $invoice->customer_user_id)->lockForUpdate()->get();
            $already = $existing->firstWhere('invoice_id', $invoice->id);
            if ($already) {
                return $already;
            }

            $beforeCents = (int) round($existing->where('status', '!=', AffiliateCommission::STATUS_CANCELLED)->sum(fn ($c) => (float) $c->gross_amount) * 100);
            $grossCents = (int) round((float) $invoice->total * 100);
            $version = AffiliateRateVersion::current();
            $result = $this->calculator->forPurchase($version->tiers, $beforeCents, $grossCents);

            return AffiliateCommission::query()->create([
                'affiliate_id' => $link->affiliate_id,
                'customer_user_id' => $invoice->customer_user_id,
                'invoice_id' => $invoice->id,
                'affiliate_rate_version_id' => $version->id,
                'gross_amount' => $this->money($grossCents),
                'cumulative_before' => $this->money($beforeCents),
                'cumulative_after' => $this->money($beforeCents + $grossCents),
                'amount' => $this->money($result['amount_cents']),
                'breakdown' => $result['breakdown'],
                'status' => AffiliateCommission::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Admin mengesahkan bayaran keseluruhan projek selesai → semua komisyen PENDING bagi invois projek itu menjadi RELEASED.
     * Bayaran invois sahaja TIDAK melepaskan komisyen. Idempotent.
     *
     * @param list<int> $invoiceIds
     */
    public function releaseForInvoices(array $invoiceIds): int
    {
        if ($invoiceIds === []) {
            return 0;
        }

        return AffiliateCommission::query()->whereIn('invoice_id', $invoiceIds)->where('status', AffiliateCommission::STATUS_PENDING)
            ->update(['status' => AffiliateCommission::STATUS_RELEASED, 'released_at' => now(), 'updated_at' => now()]);
    }

    /** Refund projek sebelum START → komisyen PENDING projek itu dibatalkan. Komisyen RELEASED tidak disentuh. */
    public function cancelForInvoices(array $invoiceIds): int
    {
        if ($invoiceIds === []) {
            return 0;
        }

        return AffiliateCommission::query()->whereIn('invoice_id', $invoiceIds)->where('status', AffiliateCommission::STATUS_PENDING)
            ->update(['status' => AffiliateCommission::STATUS_CANCELLED, 'cancelled_at' => now(), 'updated_at' => now()]);
    }

    private function money(int $cents): string { return number_format($cents / 100, 2, '.', ''); }
}
