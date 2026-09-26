<?php

namespace App\Http\Controllers;

use App\Engines\Billing\Services\BillplzPaymentService;
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

        $invoice = $payment->invoice;

        return response()->json([
            'found' => true,
            'status' => $payment->status,
            'invoice' => [
                'number' => $invoice->number,
                'total' => number_format((float) $invoice->total, 2),
                'amount_paid' => number_format((float) $invoice->amount_paid, 2),
                'outstanding' => number_format($invoice->outstandingCents() / 100, 2),
                'status' => $invoice->status,
            ],
            'paid_amount' => $payment->paid_amount_cents !== null ? number_format($payment->paid_amount_cents / 100, 2) : null,
        ]);
    }
}
