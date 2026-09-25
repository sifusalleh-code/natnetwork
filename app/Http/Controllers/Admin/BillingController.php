<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Services\BillplzPaymentService;
use App\Engines\Billing\Services\InvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class BillingController extends Controller
{
    public function invoices(Request $request): View
    {
        $mode = $request->query('mode') === 'sandbox' ? 'sandbox' : 'production';

        return view('admin.billing.invoices', [
            'mode' => $mode,
            'invoices' => Invoice::query()->where('is_sandbox', $mode === 'sandbox')->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function invoice(Invoice $invoice): View
    {
        return view('admin.billing.invoice', ['invoice' => $invoice->load(['payments.receipt', 'receipts'])]);
    }

    public function payments(Request $request): View
    {
        $mode = $request->query('mode') === 'sandbox' ? 'sandbox' : 'production';

        return view('admin.billing.payments', [
            'mode' => $mode,
            'payments' => Payment::query()->with('invoice')->where('is_sandbox', $mode === 'sandbox')->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function testFlow(): View
    {
        return view('admin.billing.test', ['settings' => BillingSetting::current()]);
    }

    public function startTestFlow(Request $request, InvoiceService $invoices, BillplzPaymentService $payments): RedirectResponse
    {
        $settings = BillingSetting::current();
        if (! $settings->isSandbox() || ! $settings->hasCredentials(BillingSetting::MODE_SANDBOX)) {
            throw ValidationException::withMessages(['amount' => ['Uji aliran bayaran hanya boleh dibuat dalam mod SANDBOX dengan kunci sandbox lengkap.']]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'type' => ['required', Rule::in(Invoice::TYPES)],
        ]);

        $invoice = $invoices->issue($data['type'], ['name' => $data['name'], 'email' => mb_strtolower($data['email'])], [
            ['description' => 'Ujian aliran bayaran (sandbox)', 'quantity' => 1, 'unit_price' => $data['amount']],
        ]);

        try {
            $payment = $payments->start($invoice);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.billing.invoice', $invoice)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->away($payment->gateway_url);
    }

    public function payInvoice(Invoice $invoice, BillplzPaymentService $payments): RedirectResponse
    {
        abort_unless($invoice->is_sandbox, 403);

        try {
            $payment = $payments->start($invoice);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.billing.invoice', $invoice)->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->away($payment->gateway_url);
    }

    public function purgeSandbox(Request $request, BillplzPaymentService $payments, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Sila sahkan pemadaman data sandbox.']);

        $counts = $payments->purgeSandbox();
        $audit->record('SANDBOX_BILLING_PURGED', Auth::guard('admin')->user(), null, null, $counts);

        return redirect()->route('admin.billing.invoices', ['mode' => 'sandbox'])
            ->with('status', "Data sandbox dipadam: {$counts['invoices']} invois, {$counts['payments']} bayaran, {$counts['receipts']} resit.");
    }
}
