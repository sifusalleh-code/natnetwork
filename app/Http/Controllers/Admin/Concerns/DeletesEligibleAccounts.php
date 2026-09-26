<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Billing\Models\Receipt;
use App\Engines\Billing\Models\Refund;
use App\Engines\Project\Models\Project;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SlotHold;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Padam akaun ahli secara pukal (bulk) — HANYA jika rekod itu tiada sebarang transaksi/rekod
 * berkaitan (quotation, order, projek, komisen, modal, dll). Ini menguatkuasakan AGENTS.md §14:
 * "Jangan letakkan Edit/Delete pada semua record secara membuta tuli" — rekod yang mempunyai
 * sejarah transaksi dilangkau (bukan dipaksa padam), bukan diveto secara manual bagi setiap jadual
 * berkaitan, sebaliknya kekangan foreign key pangkalan data sendiri (restrictOnDelete) menjadi
 * pemeriksa muktamad: jika ada rujukan, padam akan gagal dan direkod sebagai "dilangkau".
 */
trait DeletesEligibleAccounts
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: list<int>, skipped: list<int>}
     */
    private function deleteEligible(string $modelClass, array $ids): array
    {
        $deleted = [];
        $skipped = [];

        foreach ($ids as $id) {
            /** @var Model|null $record */
            $record = $modelClass::query()->find($id);
            if (! $record) {
                continue;
            }
            try {
                DB::transaction(function () use ($record): void {
                    $record->delete();
                });
                $deleted[] = $id;
            } catch (QueryException) {
                // Kekangan foreign key (restrictOnDelete): akaun ini mempunyai rekod/transaksi berkaitan.
                $skipped[] = $id;
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * Bersihkan rantaian quotation/order/projek/invois SANDBOX sahaja bagi SATU pelanggan (Keputusan
     * Owner 25 Sep 2026 #2: rekod sandbox boleh dipadam admin). Rekod production pelanggan yang sama
     * tidak disentuh — jika masih ada quotation/order production, akaun tetap dilangkau seperti biasa
     * selepas ini oleh deleteEligible() melalui kekangan foreign key. Dipanggil sebelum deleteEligible()
     * untuk akaun Client supaya "pendaftaran ujian" (hanya ada transaksi sandbox) benar-benar padam.
     */
    private function purgeCustomerSandboxFootprint(int $customerUserId): void
    {
        DB::transaction(function () use ($customerUserId): void {
            $requestIds = ProjectRequest::query()->where('customer_user_id', $customerUserId)->pluck('id');
            $quotationIds = Quotation::query()->where('is_sandbox', true)->whereIn('project_request_id', $requestIds)->pluck('id');

            if ($quotationIds->isNotEmpty()) {
                $projectIds = Project::query()->where('is_sandbox', true)->whereIn('quotation_id', $quotationIds)->pluck('id');
                $invoiceIds = Invoice::query()->where('is_sandbox', true)->where('source_type', 'Quotation')->whereIn('source_id', $quotationIds)->pluck('id');

                Refund::query()->where('is_sandbox', true)->whereIn('project_id', $projectIds)->delete();
                ChangeRequest::query()->where('is_sandbox', true)->whereIn('quotation_id', $quotationIds)->delete();
                Receipt::query()->whereIn('invoice_id', $invoiceIds)->delete();
                Payment::query()->whereIn('invoice_id', $invoiceIds)->delete();
                Project::query()->whereIn('id', $projectIds)->delete();
                Order::query()->where('is_sandbox', true)->whereIn('quotation_id', $quotationIds)->delete();
                SlotHold::query()->whereIn('quotation_id', $quotationIds)->delete();
                QuotationPaymentPlan::query()->whereIn('quotation_id', $quotationIds)->delete();
                Quotation::query()->whereIn('id', $quotationIds)->delete();
                Invoice::query()->whereIn('id', $invoiceIds)->delete();
            }

            // Project Request/Master Specification tanpa quotation langsung (sandbox baru dibersih,
            // atau tidak pernah sampai ke quotation) — selamat dipadam, quotation production dikekalkan.
            foreach ($requestIds as $requestId) {
                if (! Quotation::query()->where('project_request_id', $requestId)->exists()) {
                    MasterSpecification::query()->where('project_request_id', $requestId)->delete();
                    ProjectRequest::query()->whereKey($requestId)->delete();
                }
            }
        });
    }
}
