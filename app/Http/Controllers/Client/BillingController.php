<?php

namespace App\Http\Controllers\Client;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Receipt;
use App\Engines\Billing\Services\BillplzPaymentService;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class BillingController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::query()->where('customer_user_id', Auth::guard('client')->id())->with('receipts')->latest('issued_at')->get();

        return view('client.portal.billing', [
            'invoices' => $invoices,
            'total' => $invoices->where('status', '!=', Invoice::STATUS_VOID)->sum(fn ($i) => (float) $i->total),
            'paid' => $invoices->sum(fn ($i) => (float) $i->amount_paid),
            'outstanding' => $invoices->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])->sum(fn ($i) => $i->outstandingCents()) / 100,
        ]);
    }

    public function invoice(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);

        return view('client.portal.invoice', ['invoice' => $invoice->load(['payments.receipt', 'receipts', 'refunds'])]);
    }

    public function pay(Invoice $invoice, BillplzPaymentService $payments, SchedulingService $scheduling): RedirectResponse
    {
        $this->authorizeInvoice($invoice);

        // Deposit projek: slot mesti dipegang (HELD aktif) atau telah ditempah sebelum bayaran dimulakan.
        if ($invoice->type === 'DEPOSIT' && $invoice->source_type === 'Quotation') {
            $quotation = Quotation::query()->find($invoice->source_id);
            if ($quotation && ! $scheduling->currentHold($quotation)) {
                return redirect()->route('client.quotations.slot', $quotation)->withErrors(['slot' => 'Hold slot anda telah tamat. Sila pilih semula minggu mula sebelum membayar deposit.']);
            }
        }

        try {
            $payment = $payments->start($invoice);
        } catch (RuntimeException $exception) {
            return redirect()->route('client.billing.invoice', $invoice)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->away($payment->gateway_url);
    }

    public function receipt(Receipt $receipt): View
    {
        $this->authorizeInvoice($receipt->invoice);

        return view('client.portal.receipt', ['receipt' => $receipt->load('invoice', 'payment')]);
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless($invoice->customer_user_id === Auth::guard('client')->id(), 404);
    }
}
