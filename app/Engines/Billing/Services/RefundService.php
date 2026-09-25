<?php

namespace App\Engines\Billing\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Events\RefundRecorded;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\Refund;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Billing: refund standard hanya sebelum START PROJECT. Wang dipindahkan manual oleh admin; sistem merekod.
 * Refund membatalkan projek (slot dilepaskan), order, CR terbuka dan komisyen affiliate projek.
 */
class RefundService
{
    public function __construct(private readonly DocumentNumberService $numbers, private readonly AuditLogger $audit)
    {
    }

    public static function projectInvoices(Project $project): Collection
    {
        return ProjectPaymentService::allProjectInvoices($project->quotation_id);
    }

    public static function eligible(Project $project): bool
    {
        return $project->started_at === null && ! $project->isClosed();
    }

    /**
     * @param array<int|string, string|float|null> $amounts invoice_id => amaun RM
     * @return Collection<int, Refund>
     */
    public function refundProject(Admin $admin, Project $project, array $amounts, string $reason, string $transferReference): Collection
    {
        return DB::transaction(function () use ($admin, $project, $amounts, $reason, $transferReference): Collection {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if (! self::eligible($project)) {
                throw ValidationException::withMessages(['refund' => ['Refund standard hanya dibenarkan sebelum START PROJECT dan sebelum projek ditutup.']]);
            }

            $invoices = self::projectInvoices($project)->keyBy('id');
            $refunds = collect();
            $totalCents = 0;

            foreach ($amounts as $invoiceId => $amount) {
                $cents = (int) round(((float) $amount) * 100);
                if ($cents <= 0) {
                    continue;
                }
                $invoice = $invoices->get((int) $invoiceId);
                if (! $invoice) {
                    throw ValidationException::withMessages(['refund' => ['Invois bukan milik projek ini.']]);
                }
                $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
                if ($cents > $invoice->refundableCents()) {
                    throw ValidationException::withMessages(['refund' => ["Amaun refund {$invoice->number} melebihi baki yang boleh direfund (RM ".number_format($invoice->refundableCents() / 100, 2).').']]);
                }

                $payment = Payment::query()->where('invoice_id', $invoice->id)->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_REFUNDED])->latest('id')->first();
                $refunds->push(Refund::query()->create([
                    'number' => $this->numbers->next('RF', $invoice->is_sandbox),
                    'is_sandbox' => $invoice->is_sandbox,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment?->id,
                    'project_id' => $project->id,
                    'customer_user_id' => $invoice->customer_user_id,
                    'amount' => number_format($cents / 100, 2, '.', ''),
                    'reason' => $reason,
                    'transfer_reference' => $transferReference,
                    'refunded_by_admin_id' => $admin->id,
                    'refunded_at' => now(),
                ]));

                $refundedCents = (int) round((float) $invoice->amount_refunded * 100) + $cents;
                $invoice->forceFill(['amount_refunded' => number_format($refundedCents / 100, 2, '.', '')])->save();
                if ($payment) {
                    $full = $refundedCents >= (int) round((float) $invoice->amount_paid * 100);
                    $payment->forceFill(['status' => $full ? Payment::STATUS_REFUNDED : Payment::STATUS_PARTIALLY_REFUNDED])->save();
                }
                $totalCents += $cents;
            }

            if ($refunds->isEmpty()) {
                throw ValidationException::withMessages(['refund' => ['Isi sekurang-kurangnya satu amaun refund.']]);
            }

            // Invois projek yang belum dibayar langsung tidak lagi sah.
            foreach ($invoices as $open) {
                if ($open->status === Invoice::STATUS_ISSUED && (float) $open->amount_paid <= 0) {
                    $open->forceFill(['status' => Invoice::STATUS_VOID, 'voided_at' => now()])->save();
                }
            }

            $this->audit->record('PROJECT_REFUNDED', $admin, $project, null, [
                'refunds' => $refunds->map->only(['number', 'invoice_id', 'amount'])->all(), 'transfer_reference' => $transferReference,
            ], $reason);

            RefundRecorded::dispatch($admin, $project, $refunds->pluck('id')->all(), $invoices->keys()->all(), $reason, $totalCents);

            return $refunds;
        });
    }
}
