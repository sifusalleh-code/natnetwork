<?php

namespace App\Engines\Sales\Services;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Services\SchedulingService;

/**
 * Paparan sahaja: status keseluruhan Start Project (Maklumat → Soal jawab → Master Specification → Quotation → Slot & Bayaran → Portal).
 * Tiada perubahan status dibuat di sini; setiap langkah dikawal oleh engine pemiliknya.
 */
class StartProjectJourneyService
{
    public function __construct(private readonly SchedulingService $scheduling)
    {
    }

    public function for(BuilderSession $session, int $stepCount): array
    {
        $request = ProjectRequest::query()->where('builder_session_id', $session->id)->first();
        $specification = $request?->specifications()->latest('version')->first();
        $approved = $request?->specifications()->where('status', MasterSpecification::STATUS_APPROVED)->latest('version')->first();
        $quotation = $approved ? Quotation::query()->where('master_specification_id', $approved->id)->first() : null;
        $invoice = $quotation ? Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->where('type', 'DEPOSIT')->where('status', '!=', Invoice::STATUS_VOID)->latest('id')->first() : null;
        $order = $quotation ? Order::query()->where('quotation_id', $quotation->id)->first() : null;
        $attempts = $invoice ? Payment::query()->where('invoice_id', $invoice->id)->count() : 0;
        $unpaid = $invoice && in_array($invoice->status, [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID], true);
        $hold = $quotation && $quotation->status === Quotation::STATUS_ACCEPTED ? $this->scheduling->currentHold($quotation) : null;
        $answered = min($stepCount, (int) $session->current_step);

        $steps = [];
        $steps[] = ['key' => 'info', 'label' => 'Maklumat anda', 'state' => 'done', 'detail' => $session->contact_name.' · '.$session->contact_email];
        $steps[] = ['key' => 'answers', 'label' => 'Soal jawab', 'state' => $specification || $answered >= $stepCount ? 'done' : 'current',
            'detail' => $specification || $answered >= $stepCount ? 'Selesai' : $answered.' / '.$stepCount.' langkah (disimpan automatik)'];

        $specState = match (true) {
            (bool) $approved => ['done', 'Diluluskan (versi '.$approved->version.')', null],
            (bool) $specification => ['current', 'Draf sedia untuk disemak dan diluluskan', ['Semak & luluskan', route('specification.show')]],
            $answered >= $stepCount => ['current', 'Jana daripada jawapan anda', ['Jana Master Specification', route('specification.show')]],
            default => ['todo', 'Selepas soal jawab selesai', null],
        };
        $steps[] = ['key' => 'specification', 'label' => 'Master Specification', 'state' => $specState[0], 'detail' => $specState[1], 'action' => $specState[2]];

        $quoteState = match (true) {
            ! $quotation => ['todo', 'Dijana selepas spesifikasi diluluskan', null],
            $quotation->status === Quotation::STATUS_ACCEPTED => ['done', 'Diterima · RM '.number_format((float) $quotation->total_amount, 2), null],
            $quotation->status === Quotation::STATUS_REVIEW_REQUIRED, $quotation->status === Quotation::STATUS_DRAFT => ['waiting', 'Sedang disediakan oleh pasukan kami. Anda akan dimaklumkan melalui emel.', null],
            in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_VIEWED], true) => ['current', ($quotation->number ?? 'Quotation').' · RM '.number_format((float) $quotation->total_amount, 2), ['Semak & terima quotation', route('client.quotations.show', $quotation)]],
            default => ['waiting', 'Quotation '.strtolower($quotation->status).'. Sila hubungi kami.', null],
        };
        $steps[] = ['key' => 'quotation', 'label' => 'Quotation', 'state' => $quoteState[0], 'detail' => $quoteState[1], 'action' => $quoteState[2]];

        $payState = match (true) {
            (bool) $order => ['done', 'Bayaran disahkan · Order '.$order->number, null],
            ! $quotation || $quotation->status !== Quotation::STATUS_ACCEPTED => ['todo', 'Pilih minggu mula dan bayar 50% atau 100%', null],
            $unpaid && $attempts > 0 => ['failed', 'Bayaran '.$invoice->number.' belum berjaya. Teruskan bil yang sama atau reset Start Project.', $hold ? ['Teruskan bayaran (bil sama)', route('client.billing.invoice', $invoice)] : ['Pilih semula slot & teruskan bil sama', route('client.quotations.slot', $quotation)]],
            $unpaid => ['current', 'Invois '.$invoice->number.' · RM '.number_format($invoice->outstandingCents() / 100, 2), $hold ? ['Bayar sekarang', route('client.billing.invoice', $invoice)] : ['Pilih semula slot', route('client.quotations.slot', $quotation)]],
            default => ['current', 'Pilih minggu mula dan cara bayaran', ['Pilih slot & cara bayaran', route('client.quotations.slot', $quotation)]],
        };
        $steps[] = ['key' => 'payment', 'label' => 'Slot & Bayaran', 'state' => $payState[0], 'detail' => $payState[1], 'action' => $payState[2]];
        $steps[] = ['key' => 'portal', 'label' => 'Portal Client', 'state' => $order ? 'current' : 'todo', 'detail' => $order ? 'Projek anda telah disahkan' : 'Dibuka selepas bayaran berjaya', 'action' => $order ? ['Masuk ke Portal', route('client.dashboard')] : null];

        return [
            'steps' => $steps,
            'specification' => $specification,
            'quotation' => $quotation,
            'invoice' => $invoice,
            'order' => $order,
            'paymentFailed' => $unpaid && $attempts > 0,
            'canReset' => ! $order && ! ($invoice && (float) $invoice->amount_paid > 0),
            'started' => (bool) $specification,
        ];
    }
}
