<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Billing\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Senarai pelanggan affiliate beserta bil production dan komisyen setiap bil (hanya milik affiliate ini). */
class AffiliateCustomerService
{
    public const INVOICE_STATUS_LABELS = [
        Invoice::STATUS_ISSUED => 'Belum dibayar',
        Invoice::STATUS_PARTIALLY_PAID => 'Dibayar separa',
        Invoice::STATUS_PAID => 'Dibayar',
        Invoice::STATUS_VOID => 'Dibatalkan',
    ];

    public const COMMISSION_STATUS_LABELS = [
        AffiliateCommission::STATUS_PENDING => 'Pending',
        AffiliateCommission::STATUS_RELEASED => 'Released',
        AffiliateCommission::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public function forAffiliate(Affiliate $affiliate): Collection
    {
        $links = AffiliateClientLink::query()->with('customer:id,name')->where('affiliate_id', $affiliate->id)->latest('linked_at')->get();
        $customerIds = $links->pluck('customer_user_id');

        $invoices = Invoice::query()->production()->whereIn('customer_user_id', $customerIds)->orderBy('issued_at')->get()->groupBy('customer_user_id');
        $commissions = AffiliateCommission::query()->where('affiliate_id', $affiliate->id)->whereIn('customer_user_id', $customerIds)->get()->keyBy('invoice_id');

        return $links->map(function (AffiliateClientLink $link) use ($invoices, $commissions): array {
            $bills = ($invoices->get($link->customer_user_id) ?? collect())->map(fn (Invoice $invoice): array => [
                'number' => $invoice->number,
                'date' => $invoice->issued_at,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'status_label' => self::INVOICE_STATUS_LABELS[$invoice->status] ?? $invoice->status,
                'commission' => $commissions->get($invoice->id)?->amount,
                'commission_status' => $commissions->get($invoice->id)?->status,
                'commission_status_label' => ($s = $commissions->get($invoice->id)?->status) ? (self::COMMISSION_STATUS_LABELS[$s] ?? $s) : null,
            ]);
            $counted = $bills->where('status', '!=', Invoice::STATUS_VOID);

            return [
                'name' => self::maskName((string) $link->customer?->name),
                'linked_at' => $link->linked_at,
                'bills' => $bills->values(),
                'total_sales' => number_format($counted->sum(fn ($b) => (float) $b['total']), 2, '.', ''),
                'total_commission' => number_format($bills->where('commission_status', '!=', AffiliateCommission::STATUS_CANCELLED)->sum(fn ($b) => (float) $b['commission']), 2, '.', ''),
            ];
        });
    }

    /** "Siti Aminah Ahmad" → "Siti A*** A***"; "Aminah" → "Ami***". */
    public static function maskName(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return 'Pelanggan';
        }
        if (count($words) === 1) {
            return Str::substr($words[0], 0, 3).'***';
        }

        return $words[0].' '.collect(array_slice($words, 1))->map(fn (string $w) => Str::substr($w, 0, 1).'***')->implode(' ');
    }
}
