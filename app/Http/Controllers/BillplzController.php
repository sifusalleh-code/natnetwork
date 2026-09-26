<?php

namespace App\Http\Controllers;

use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\Receipt;
use App\Engines\Billing\Services\BillplzPaymentService;
use App\Engines\Partnership\Models\PartnerCapital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BillplzController extends Controller
{
    /** Callback pelayan-ke-pelayan Billplz (POST). Data mentah digunakan supaya tandatangan tidak terjejas oleh middleware trim/null. */
    public function callback(Request $request, BillplzPaymentService $payments): Response
    {
        parse_str((string) $request->getContent(), $payload);
        if ($payload === []) {
            $payload = $request->request->all();
        }

        return match ($payments->handleCallback($payload)) {
            BillplzPaymentService::RESULT_OK => response('OK', 200),
            BillplzPaymentService::RESULT_INVALID_SIGNATURE => response('Invalid signature', 403),
            default => response('Unknown bill', 404),
        };
    }

    /** Pelanggan kembali dari Billplz (GET). Bukan bukti bayaran; status dibaca daripada rekod yang disahkan melalui callback. */
    public function return(Request $request, BillplzPaymentService $payments): View
    {
        parse_str((string) $request->server('QUERY_STRING'), $query);
        $billplz = is_array($query['billplz'] ?? null) ? $query['billplz'] : [];
        $payment = $payments->paymentForRedirect($billplz)?->load('invoice');

        // Bayaran modal Partnership: halaman status khas (resit lengkap selepas callback disahkan).
        if ($payment?->invoice?->source_type === 'PartnerCapital') {
            return view('partner.payment-status', [
                'payment' => $payment,
                'capital' => PartnerCapital::query()->find($payment->invoice->source_id),
                'state' => $this->state($payment),
            ]);
        }

        return view('billing.return', ['payment' => $payment]);
    }

    /**
     * Status bayaran dalam JSON, dipoll oleh halaman "Status Bayaran" (billing.return) supaya paparan
     * bertukar automatik sebaik sahaja callback Billplz mengesahkan bayaran — tanpa client refresh
     * halaman. Kebenaran sama seperti return(): tandatangan redirect Billplz, bukan sesi log masuk.
     */
    public function status(Request $request, BillplzPaymentService $payments): JsonResponse
    {
        parse_str((string) $request->server('QUERY_STRING'), $query);
        $billplz = is_array($query['billplz'] ?? null) ? $query['billplz'] : [];
        $payment = $payments->paymentForRedirect($billplz)?->load('invoice');

        if (! $payment) {
            return response()->json(['found' => false]);
        }

        return response()->json(['found' => true] + $this->state($payment));
    }

    /** @return array<string, mixed> */
    private function state(Payment $payment): array
    {
        $invoice = $payment->invoice;
        $receipt = Receipt::query()->where('payment_id', $payment->id)->latest('id')->first();

        return [
            'status' => $payment->status,
            'invoice' => [
                'number' => $invoice->number,
                'total' => number_format((float) $invoice->total, 2),
                'amount_paid' => number_format((float) $invoice->amount_paid, 2),
                'outstanding' => number_format($invoice->outstandingCents() / 100, 2),
                'status' => $invoice->status,
            ],
            'paid_amount' => $payment->paid_amount_cents !== null ? number_format($payment->paid_amount_cents / 100, 2) : null,
            'paid_at' => $payment->paid_at?->timezone(config('app.timezone'))->format('d M Y, h:i A'),
            'bill_id' => $payment->gateway_bill_id,
            'receipt' => $receipt ? [
                'number' => $receipt->number,
                'amount' => number_format((float) $receipt->amount, 2),
                'issued_at' => $receipt->issued_at?->format('d M Y, h:i A'),
            ] : null,
        ];
    }
}
