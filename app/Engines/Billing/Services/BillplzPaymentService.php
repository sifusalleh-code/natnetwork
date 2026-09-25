<?php

namespace App\Engines\Billing\Services;

use App\Adapters\Billplz\BillplzClient;
use App\Engines\Billing\Events\InvoicePaid;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\Receipt;
use App\Engines\Billing\Models\Refund;
use App\Engines\Project\Models\Project;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SlotHold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BillplzPaymentService
{
    public const RESULT_OK = 'OK';
    public const RESULT_INVALID_SIGNATURE = 'INVALID_SIGNATURE';
    public const RESULT_UNKNOWN_BILL = 'UNKNOWN_BILL';

    public function __construct(
        private readonly BillplzClient $billplz,
        private readonly DocumentNumberService $numbers,
    ) {
    }

    /** Cipta bil Billplz bagi baki invois. Guna semula bil PENDING yang sama jika masih sah (klik berganda). */
    public function start(Invoice $invoice): Payment
    {
        $settings = BillingSetting::current();
        $mode = $invoice->is_sandbox ? BillingSetting::MODE_SANDBOX : BillingSetting::MODE_PRODUCTION;

        if ($settings->active_mode !== $mode) {
            throw new RuntimeException('Invois ini dicipta dalam mod '.$mode.' tetapi mod aktif sekarang ialah '.$settings->active_mode.'.');
        }
        if (! $settings->hasCredentials($mode)) {
            throw new RuntimeException('Kunci Billplz bagi mod '.$mode.' belum lengkap.');
        }
        if (! in_array($invoice->status, [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID], true) || $invoice->outstandingCents() < 1) {
            throw new RuntimeException('Invois ini tidak mempunyai baki untuk dibayar.');
        }

        $amount = $invoice->outstandingCents();
        $credentials = $settings->credentials($mode);

        $payment = DB::transaction(function () use ($invoice, $amount, $mode, $credentials): Payment {
            Invoice::query()->whereKey($invoice->id)->lockForUpdate()->first();
            $existing = Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::STATUS_PENDING)
                ->where('amount_cents', $amount)->where('gateway_mode', $mode)->whereNotNull('gateway_url')->latest('id')->first();

            return $existing ?? Payment::query()->create([
                'invoice_id' => $invoice->id,
                'is_sandbox' => $invoice->is_sandbox,
                'gateway' => 'BILLPLZ',
                'gateway_mode' => $mode,
                'gateway_collection_id' => $credentials['collection_id'],
                'amount_cents' => $amount,
                'status' => Payment::STATUS_PENDING,
            ]);
        });

        if ($payment->gateway_url) {
            return $payment;
        }

        try {
            $bill = $this->billplz->createBill($mode, $credentials, [
                'email' => (string) $invoice->customer_snapshot['email'],
                'name' => Str::limit((string) $invoice->customer_snapshot['name'], 250, ''),
                'amount' => $amount,
                'description' => Str::limit('Invois '.$invoice->number, 200, ''),
                'callback_url' => self::publicUrl('billing.billplz.callback'),
                'redirect_url' => self::publicUrl('billing.billplz.return'),
                'reference_1_label' => 'Invois',
                'reference_1' => $invoice->number,
            ]);
        } catch (Throwable $exception) {
            $payment->forceFill(['status' => Payment::STATUS_FAILED, 'review_reason' => Str::limit($exception->getMessage(), 490, '')])->save();
            Log::warning('Billplz create bill gagal', ['payment_id' => $payment->id, 'mode' => $mode, 'error' => $exception->getMessage()]);
            throw new RuntimeException('Bil pembayaran tidak dapat dicipta. Sila cuba lagi.', 0, $exception);
        }

        $payment->forceFill(['gateway_bill_id' => $bill['id'], 'gateway_url' => $bill['url']])->save();

        return $payment;
    }

    /**
     * URL awam untuk Billplz, dibina daripada APP_URL (bukan hos permintaan semasa) supaya betul
     * walaupun admin membuka sistem melalui domain tempatan seperti natnetwork.test.
     */
    public static function publicUrl(string $routeName): string
    {
        return rtrim((string) config('app.url'), '/').route($routeName, [], false);
    }

    /**
     * Proses callback pelayan Billplz. Satu-satunya bukti bayaran. Idempotent.
     *
     * @param array<string, mixed> $payload data POST mentah
     */
    public function handleCallback(array $payload): string
    {
        $payment = is_string($payload['id'] ?? null) ? Payment::query()->where('gateway_bill_id', $payload['id'])->first() : null;
        if (! $payment) {
            Log::warning('Callback Billplz untuk bil tidak dikenali', ['bill_id' => $payload['id'] ?? null]);

            return self::RESULT_UNKNOWN_BILL;
        }

        $key = (string) BillingSetting::current()->credentials($payment->gateway_mode)['x_signature_key'];
        if ($key === '' || ! $this->billplz->verify($this->billplz->callbackSignature($payload, $key), $payload['x_signature'] ?? null)) {
            Log::warning('Tandatangan callback Billplz tidak sah', ['payment_id' => $payment->id]);

            return self::RESULT_INVALID_SIGNATURE;
        }

        DB::transaction(function () use ($payment, $payload): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_REVIEW_REQUIRED, Payment::STATUS_REFUNDED, Payment::STATUS_PARTIALLY_REFUNDED], true)) {
                return; // callback berulang — tiada kesan kewangan baharu
            }

            $paid = ($payload['paid'] ?? null) === 'true' && ($payload['state'] ?? null) === 'paid';
            if (! $paid) {
                $payment->forceFill(['status' => Payment::STATUS_FAILED, 'callback_payload' => $payload, 'verified_at' => now()])->save();

                return;
            }

            $paidCents = ctype_digit((string) ($payload['paid_amount'] ?? '')) ? (int) $payload['paid_amount'] : null;
            $reason = null;
            if (($payload['collection_id'] ?? null) !== $payment->gateway_collection_id) {
                $reason = 'Collection ID tidak sepadan.';
            } elseif ($paidCents !== $payment->amount_cents || (string) ($payload['amount'] ?? '') !== (string) $payment->amount_cents) {
                $reason = 'Jumlah bayaran tidak sepadan (dijangka '.$payment->amount_cents.' sen, diterima '.($payload['paid_amount'] ?? '-').' sen).';
            }
            if (! $reason && Invoice::query()->whereKey($payment->invoice_id)->value('status') === Invoice::STATUS_VOID) {
                $reason = 'Bayaran diterima untuk invois yang telah dibatalkan (Start Project di-reset). Semak dan pulangkan jika perlu.';
            }

            if ($reason) {
                $payment->forceFill(['status' => Payment::STATUS_REVIEW_REQUIRED, 'review_reason' => $reason, 'paid_amount_cents' => $paidCents, 'callback_payload' => $payload, 'verified_at' => now()])->save();

                return;
            }

            $payment->forceFill([
                'status' => Payment::STATUS_PAID,
                'paid_amount_cents' => $paidCents,
                'callback_payload' => $payload,
                'verified_at' => now(),
                'paid_at' => now(),
            ])->save();

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $newPaidCents = (int) round((float) $invoice->amount_paid * 100) + $paidCents;
            $totalCents = (int) round((float) $invoice->total * 100);
            $invoice->forceFill([
                'amount_paid' => number_format($newPaidCents / 100, 2, '.', ''),
                'status' => $newPaidCents >= $totalCents ? Invoice::STATUS_PAID : Invoice::STATUS_PARTIALLY_PAID,
                'paid_at' => $newPaidCents >= $totalCents ? now() : $invoice->paid_at,
            ])->save();

            Receipt::query()->create([
                'number' => $this->numbers->next('RCP', $payment->is_sandbox),
                'is_sandbox' => $payment->is_sandbox,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => number_format($paidCents / 100, 2, '.', ''),
                'issued_at' => now(),
            ]);

            if ($invoice->status === Invoice::STATUS_PAID) {
                InvoicePaid::dispatch($invoice);
            }
        });

        return self::RESULT_OK;
    }

    /**
     * Sahkan tandatangan redirect pelayar. BUKAN bukti bayaran — hanya untuk memaparkan status semasa dari pangkalan data.
     *
     * @param array<string, mixed> $billplz parameter billplz[...] mentah
     */
    public function paymentForRedirect(array $billplz): ?Payment
    {
        $payment = is_string($billplz['id'] ?? null) ? Payment::query()->where('gateway_bill_id', $billplz['id'])->first() : null;
        if (! $payment) {
            return null;
        }

        $key = (string) BillingSetting::current()->credentials($payment->gateway_mode)['x_signature_key'];

        return $key !== '' && $this->billplz->verify($this->billplz->redirectSignature($billplz, $key), $billplz['x_signature'] ?? null) ? $payment : null;
    }

    /**
     * Padam KESELURUHAN rantaian rekod sandbox (TEST-*): refund, change request, resit, bayaran, projek
     * (milestone/kandungan/fail/log status projek terpadam serentak melalui cascade), order, slot hold,
     * quotation dan invois. Rekod production tidak pernah disentuh (Keputusan Owner 25 Sep 2026, #2 & #8).
     * Susunan padam mengikut kekangan foreign key supaya tiada rekod sandbox yang tertinggal.
     */
    public function purgeSandbox(): array
    {
        return DB::transaction(function (): array {
            $counts = [
                'refunds' => Refund::query()->where('is_sandbox', true)->count(),
                'change_requests' => ChangeRequest::query()->where('is_sandbox', true)->count(),
                'receipts' => Receipt::query()->where('is_sandbox', true)->count(),
                'payments' => Payment::query()->where('is_sandbox', true)->count(),
                'projects' => Project::query()->where('is_sandbox', true)->count(),
                'orders' => Order::query()->where('is_sandbox', true)->count(),
                'slot_holds' => SlotHold::query()->where('is_sandbox', true)->count(),
                'quotations' => Quotation::query()->where('is_sandbox', true)->count(),
                'invoices' => Invoice::query()->where('is_sandbox', true)->count(),
            ];

            Refund::query()->where('is_sandbox', true)->delete();
            ChangeRequest::query()->where('is_sandbox', true)->delete();
            Receipt::query()->where('is_sandbox', true)->delete();
            Payment::query()->where('is_sandbox', true)->delete();
            Project::query()->where('is_sandbox', true)->delete();
            Order::query()->where('is_sandbox', true)->delete();
            SlotHold::query()->where('is_sandbox', true)->delete();
            Quotation::query()->where('is_sandbox', true)->delete();
            Invoice::query()->where('is_sandbox', true)->delete();

            return $counts;
        });
    }
}
