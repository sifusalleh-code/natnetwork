<?php

namespace App\Engines\Billing\Listeners;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Billing\Services\PaymentPlanService;
use App\Engines\Project\Events\ProjectReachedReview;
use App\Engines\Project\Models\Project;
use Illuminate\Support\Facades\DB;

/** Billing: progress melepasi 80% kali pertama → invois akhir (baki nilai quotation) dijana TEPAT sekali. */
class CreateFinalInvoice
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function handle(ProjectReachedReview $event): void
    {
        DB::transaction(function () use ($event): void {
            $project = Project::query()->lockForUpdate()->findOrFail($event->project->id);
            if ($project->final_invoice_id) {
                return;
            }

            $quotation = $project->quotation()->with('request.customer')->firstOrFail();
            $invoicedCents = (int) Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)
                ->where('status', '!=', Invoice::STATUS_VOID)->get()->sum(fn ($i) => (int) round((float) $i->total * 100));
            // Bayaran penuh dengan diskaun: diskaun bukan baki tertunggak.
            $remaining = (int) round((float) $quotation->total_amount * 100) - $invoicedCents - PaymentPlanService::lockedDiscountCents($quotation->id);
            if ($remaining <= 0) {
                return;
            }

            $customer = $quotation->request->customer;
            $invoice = $this->invoices->issue('FINAL_PAYMENT', [
                'name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone, 'company' => $customer->company,
            ], [[
                'description' => 'Bayaran akhir — '.$project->number.' (Quotation '.$quotation->number.')',
                'quantity' => 1,
                'unit_price' => number_format($remaining / 100, 2, '.', ''),
            ]], $customer, null, 'Quotation', $quotation->id);

            $project->forceFill(['final_invoice_id' => $invoice->id])->save();
        });
    }
}
