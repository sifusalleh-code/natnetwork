<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Billing\Models\Receipt;
use App\Engines\Billing\Models\Refund;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Engines\Project\Models\Project;
use App\Engines\Sales\Models\BuilderFile;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SlotHold;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Padam akaun ahli secara pukal (bulk) — AGENTS.md §14: bukan Delete membuta tuli.
 *
 * Bagi setiap akaun, dalam SATU transaksi:
 * 1. rekod yang bukan rekod kewangan/kontrak production dibersihkan (sandbox, pra-bayaran,
 *    data penjejakan/log masuk — lihat purge*() di bawah);
 * 2. akaun itu cuba dipadam.
 * Jika masih ada rekod production (quotation ACCEPTED, invois/order/projek/komisen/modal/payout
 * sebenar), kekangan foreign key (restrictOnDelete) menggagalkan padam dan KESELURUHAN transaksi
 * dibatalkan — akaun dan semua rekodnya kekal utuh, direkod sebagai "dilangkau".
 */
trait DeletesEligibleAccounts
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: list<int>, skipped: list<int>, blockers: list<string>}
     */
    private function deleteEligible(string $modelClass, array $ids): array
    {
        $deleted = [];
        $skipped = [];
        $blockers = [];

        foreach ($ids as $id) {
            /** @var Model|null $record */
            $record = $modelClass::query()->find($id);
            if (! $record) {
                continue;
            }
            try {
                $filePaths = DB::transaction(function () use ($record, $modelClass): array {
                    $paths = match ($modelClass) {
                        User::class => $this->purgeCustomerDeletableFootprint($record->getKey()),
                        Affiliate::class => $this->purgeAffiliateDeletableFootprint($record->getKey()),
                        Partner::class => $this->purgePartnerDeletableFootprint($record->getKey()),
                        default => [],
                    };
                    $record->delete();

                    return $paths;
                });
                // Fail fizikal hanya dipadam selepas transaksi berjaya.
                foreach ($filePaths as $path) {
                    Storage::disk('local')->delete($path);
                }
                $deleted[] = $id;
            } catch (QueryException $e) {
                // Masih ada rekod production berkaitan — semua perubahan di atas dibatalkan.
                $skipped[] = $id;
                // MySQL/MariaDB menamakan jadual yang menyekat: "... constraint fails (`db`.`orders`, ...".
                if (preg_match('/constraint fails \(`[^`]+`\.`([^`]+)`/', $e->getMessage(), $m)) {
                    $blockers[] = $m[1];
                }
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped, 'blockers' => array_values(array_unique($blockers))];
    }

    /**
     * Client: bersihkan (1) rantaian SANDBOX (Keputusan #2) dan (2) rekod PRA-BAYARAN — quotation belum
     * ACCEPTED tanpa invois/order, Master Specification, Project Request, sesi Builder (+ fail), cabaran
     * OTP, pautan rujukan affiliate (Keputusan #3). Quotation ACCEPTED dan rekod kewangan production
     * tidak disentuh.
     *
     * @return list<string> laluan fail Builder untuk dipadam selepas commit
     */
    private function purgeCustomerDeletableFootprint(int $customerUserId): array
    {
        $requestIds = ProjectRequest::query()->where('customer_user_id', $customerUserId)->pluck('id');

        // 1. Sandbox.
        $sandboxQuotationIds = Quotation::query()->where('is_sandbox', true)->whereIn('project_request_id', $requestIds)->pluck('id');
        if ($sandboxQuotationIds->isNotEmpty()) {
            $projectIds = Project::query()->where('is_sandbox', true)->whereIn('quotation_id', $sandboxQuotationIds)->pluck('id');
            $invoiceIds = Invoice::query()->where('is_sandbox', true)->where('source_type', 'Quotation')->whereIn('source_id', $sandboxQuotationIds)->pluck('id');

            Refund::query()->where('is_sandbox', true)->whereIn('project_id', $projectIds)->delete();
            ChangeRequest::query()->where('is_sandbox', true)->whereIn('quotation_id', $sandboxQuotationIds)->delete();
            Receipt::query()->whereIn('invoice_id', $invoiceIds)->delete();
            Payment::query()->whereIn('invoice_id', $invoiceIds)->delete();
            Project::query()->whereIn('id', $projectIds)->delete();
            Order::query()->where('is_sandbox', true)->whereIn('quotation_id', $sandboxQuotationIds)->delete();
            SlotHold::query()->whereIn('quotation_id', $sandboxQuotationIds)->delete();
            QuotationPaymentPlan::query()->whereIn('quotation_id', $sandboxQuotationIds)->delete();
            Quotation::query()->whereIn('id', $sandboxQuotationIds)->delete();
            Invoice::query()->whereIn('id', $invoiceIds)->delete();
        }

        // 2. Pra-bayaran: belum ACCEPTED, tiada invois (apa-apa jenis) dan tiada order.
        $prePaymentIds = Quotation::query()->whereIn('project_request_id', $requestIds)
            ->where('status', '!=', Quotation::STATUS_ACCEPTED)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('invoices')->where('invoices.source_type', 'Quotation')->whereColumn('invoices.source_id', 'quotations.id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('orders')->whereColumn('orders.quotation_id', 'quotations.id'))
            ->pluck('id');
        if ($prePaymentIds->isNotEmpty()) {
            SlotHold::query()->whereIn('quotation_id', $prePaymentIds)->delete();
            QuotationPaymentPlan::query()->whereIn('quotation_id', $prePaymentIds)->delete();
            Quotation::query()->whereIn('id', $prePaymentIds)->delete();
        }

        // Project Request tanpa quotation lagi (+ Master Specification) — selamat dipadam.
        foreach ($requestIds as $requestId) {
            if (! Quotation::query()->where('project_request_id', $requestId)->exists()) {
                MasterSpecification::query()->where('project_request_id', $requestId)->delete();
                ProjectRequest::query()->whereKey($requestId)->delete();
            }
        }

        // Sesi Builder yang tidak lagi dirujuk Project Request (jawapan & rekod fail ikut cascade).
        $sessionIds = BuilderSession::query()->where('user_id', $customerUserId)
            ->whereNotIn('id', ProjectRequest::query()->select('builder_session_id'))->pluck('id');
        $filePaths = BuilderFile::query()->whereIn('builder_session_id', $sessionIds)->pluck('path')->all();
        BuilderSession::query()->whereIn('id', $sessionIds)->delete();

        EmailOtpChallenge::query()->where('user_id', $customerUserId)->delete();
        AffiliateClientLink::query()->where('customer_user_id', $customerUserId)->delete();

        return $filePaths;
    }

    /**
     * Affiliate: klik & pautan pelanggan dirujuk — data penjejakan, bukan kewangan (komisen tidak
     * dicipta untuk bayaran sandbox). Komisen/withdrawal production tidak disentuh.
     *
     * @return list<string>
     */
    private function purgeAffiliateDeletableFootprint(int $affiliateId): array
    {
        AffiliateClientLink::query()->where('affiliate_id', $affiliateId)->delete();
        AffiliateClick::query()->where('affiliate_id', $affiliateId)->delete();

        return [];
    }

    /**
     * Partner: modal SANDBOX + invois PARTNER_CAPITAL sandbox (resit/bayaran). Modal production,
     * earning dan payout (wang sebenar) tidak disentuh.
     *
     * @return list<string>
     */
    private function purgePartnerDeletableFootprint(int $partnerId): array
    {
        $capitalIds = PartnerCapital::query()->where('partner_id', $partnerId)->where('is_sandbox', true)->pluck('id');
        if ($capitalIds->isEmpty()) {
            return [];
        }
        $invoiceIds = PartnerCapital::query()->whereIn('id', $capitalIds)->whereNotNull('invoice_id')->pluck('invoice_id');
        PartnerCapital::query()->whereIn('id', $capitalIds)->delete();

        $sandboxInvoiceIds = Invoice::query()->whereIn('id', $invoiceIds)->where('is_sandbox', true)->pluck('id');
        Receipt::query()->whereIn('invoice_id', $sandboxInvoiceIds)->delete();
        Payment::query()->whereIn('invoice_id', $sandboxInvoiceIds)->delete();
        Invoice::query()->whereIn('id', $sandboxInvoiceIds)->delete();

        return [];
    }
}
