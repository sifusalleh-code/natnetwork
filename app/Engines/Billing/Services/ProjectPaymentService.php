<?php

namespace App\Engines\Billing\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Events\ProjectPaymentCompleted;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Identity\Models\Admin;
use App\Engines\Sales\Models\Quotation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Projek = satu quotation yang diterima. Semua invois bersumberkan quotation itu. */
class ProjectPaymentService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function invoicesFor(Quotation $quotation): Collection
    {
        return Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->orderBy('issued_at')->get();
    }

    /** Semua invois projek: invois bersumberkan quotation + invois Additional Work (ChangeRequest) projek itu. */
    public static function allProjectInvoices(int $quotationId): Collection
    {
        $crIds = DB::table('change_requests')->where('quotation_id', $quotationId)->pluck('id');

        return Invoice::query()->where(fn ($q) => $q->where(fn ($q) => $q->where('source_type', 'Quotation')->where('source_id', $quotationId))
            ->orWhere(fn ($q) => $q->where('source_type', 'ChangeRequest')->whereIn('source_id', $crIds)))
            ->orderBy('issued_at')->get();
    }

    /** @return array{total_cents: int, invoiced_cents: int, paid_cents: int, remaining_to_invoice_cents: int, all_paid: bool, can_confirm: bool} */
    public function summary(Quotation $quotation): array
    {
        $invoices = $this->invoicesFor($quotation)->where('status', '!=', Invoice::STATUS_VOID);
        $additional = self::allProjectInvoices($quotation->id)->where('source_type', 'ChangeRequest')->where('status', '!=', Invoice::STATUS_VOID);
        $all = $invoices->concat($additional);
        // Nilai kontrak selepas diskaun bayar penuh (jika ada).
        $total = (int) round((float) $quotation->total_amount * 100) - PaymentPlanService::lockedDiscountCents($quotation->id);
        $additionalCents = (int) $additional->sum(fn ($i) => (int) round((float) $i->total * 100));
        $invoiced = (int) $invoices->sum(fn ($i) => (int) round((float) $i->total * 100));
        $paid = (int) $all->sum(fn ($i) => (int) round((float) $i->amount_paid * 100));
        $allPaid = $invoices->isNotEmpty() && $all->every(fn ($i) => $i->status === Invoice::STATUS_PAID);
        $accepted = $quotation->status === Quotation::STATUS_ACCEPTED;
        // Invois Additional Work yang dikeluarkan selepas pengesahan terdahulu memerlukan pengesahan baharu.
        $unconfirmedAdditional = $quotation->payment_completed_at !== null
            && $additional->contains(fn ($i) => $i->issued_at->greaterThan($quotation->payment_completed_at));

        return [
            'total_cents' => $total,
            'additional_cents' => $additionalCents,
            'invoiced_cents' => $invoiced,
            'paid_cents' => $paid,
            'remaining_to_invoice_cents' => max(0, $total - $invoiced),
            'all_paid' => $allPaid,
            'can_confirm' => $accepted && (! $quotation->payment_completed_at || $unconfirmedAdditional) && $allPaid && $paid >= $total + $additionalCents,
            'needs_additional_confirmation' => $unconfirmedAdditional,
        ];
    }

    /** Admin sahkan projek dibayar penuh → komisyen affiliate dilepaskan. Disemak semula di pelayan. Idempotent. */
    public function confirmCompleted(Admin $admin, Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($admin, $quotation): Quotation {
            $quotation = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);
            $summary = $this->summary($quotation);
            if ($quotation->payment_completed_at && ! $summary['needs_additional_confirmation']) {
                return $quotation;
            }
            if (! $summary['can_confirm']) {
                throw ValidationException::withMessages(['confirm' => ['Projek belum dibayar sepenuhnya. Semua invois mesti PAID dan jumlah dibayar mesti sama dengan nilai quotation.']]);
            }

            $quotation->forceFill(['payment_completed_at' => now(), 'payment_completed_by_admin_id' => $admin->id])->save();
            $invoiceIds = self::allProjectInvoices($quotation->id)->pluck('id')->all();
            $this->audit->record('PROJECT_PAYMENT_CONFIRMED', $admin, $quotation, null, ['number' => $quotation->number, 'invoice_ids' => $invoiceIds]);
            ProjectPaymentCompleted::dispatch($quotation, $invoiceIds);

            return $quotation;
        });
    }
}
