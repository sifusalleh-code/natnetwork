<?php

namespace App\Engines\Sales\Services;

use App\Engines\Affiliate\Services\AffiliateCommissionService;
use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Sales\Models\BuilderAnswer;
use App\Engines\Sales\Models\BuilderFile;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SlotHold;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pelanggan reset Start Project (contoh: selepas bayaran gagal). Keputusan Owner 25 Sep 2026 #3:
 * Reset HANYA dibenarkan sebelum sebarang bayaran dibuat (tiada bayaran berjaya/sedang diproses, tiada Order).
 * Invois yang telah dikeluarkan tetapi tidak pernah dibayar → VOID (rekod kewangan tidak dipadam — AGENTS §5/§8).
 * Bayaran belum selesai → CANCELLED. Slot hold, quotation, Master Specification dan Project Request lama
 * (belum pernah diterima/dibayar) DIPADAM sepenuhnya supaya pelanggan mula Start Project yang benar-benar baharu,
 * begitu juga jawapan soal jawab, fail dimuat naik, pakej dan add-on Start Project tersebut.
 */
class StartProjectResetService
{
    public function __construct(
        private readonly AffiliateCommissionService $commissions,
        private readonly AuditLogger $audit,
    ) {
    }

    public function reset(BuilderSession $session, User $customer): void
    {
        abort_unless($session->user_id === $customer->id, 403);

        $paths = DB::transaction(function () use ($session, $customer): array {
            $session = BuilderSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($session->reset_at) {
                return []; // idempotent
            }
            $requestId = ProjectRequest::query()->where('builder_session_id', $session->id)->value('id');
            $quotationIds = $requestId ? Quotation::query()->where('project_request_id', $requestId)->pluck('id')->all() : [];
            $invoices = $quotationIds ? Invoice::query()->where('source_type', 'Quotation')->whereIn('source_id', $quotationIds)->lockForUpdate()->get() : collect();

            $blocked = ($quotationIds && Order::query()->whereIn('quotation_id', $quotationIds)->exists())
                || $invoices->contains(fn (Invoice $i) => (float) $i->amount_paid > 0)
                || ($invoices->isNotEmpty() && Payment::query()->whereIn('invoice_id', $invoices->pluck('id'))
                    ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PROCESSING, Payment::STATUS_REVIEW_REQUIRED])->exists());
            if ($blocked) {
                throw ValidationException::withMessages(['reset' => ['Start Project ini tidak boleh di-reset kerana bayaran telah diterima atau sedang diproses. Sila hubungi kami.']]);
            }

            $voided = [];
            foreach ($invoices as $invoice) {
                if ($invoice->status !== Invoice::STATUS_VOID) {
                    $invoice->forceFill(['status' => Invoice::STATUS_VOID, 'voided_at' => now()])->save();
                    $voided[] = $invoice->id;
                }
            }
            if ($voided) {
                Payment::query()->whereIn('invoice_id', $voided)->where('status', Payment::STATUS_PENDING)->update(['status' => Payment::STATUS_CANCELLED, 'updated_at' => now()]);
                $this->commissions->cancelForInvoices($voided);
            }

            // Tiada bayaran pernah berjaya di sini (disekat di atas), jadi selamat memadam sepenuhnya
            // slot hold, quotation, Master Specification dan Project Request lama — bukan rekod kewangan.
            if ($quotationIds) {
                SlotHold::query()->whereIn('quotation_id', $quotationIds)->delete();
                QuotationPaymentPlan::query()->whereIn('quotation_id', $quotationIds)->delete();
                Quotation::query()->whereIn('id', $quotationIds)->delete();
            }
            if ($requestId) {
                MasterSpecification::query()->where('project_request_id', $requestId)->delete();
                ProjectRequest::query()->whereKey($requestId)->delete();
            }
            $files = BuilderFile::query()->where('builder_session_id', $session->id)->get();
            BuilderFile::query()->whereKey($files->modelKeys())->delete();
            BuilderAnswer::query()->where('builder_session_id', $session->id)->delete();
            $session->forceFill(['reset_at' => now(), 'service_package_id' => null, 'addon_ids' => null, 'current_step' => null, 'completed_at' => null])->save();

            $this->audit->record('START_PROJECT_RESET', $customer, $customer, null, [
                'builder_session_id' => $session->id, 'project_request_id' => $requestId,
                'deleted_quotation_ids' => $quotationIds, 'voided_invoice_ids' => $voided,
                'deleted_answers_and_files' => true, 'deleted_file_count' => $files->count(),
            ]);

            return $files->pluck('path')->filter()->all();
        });

        // Fail fizikal dipadam hanya selepas transaksi berjaya.
        if ($paths) {
            Storage::disk('local')->delete($paths);
        }
    }
}
