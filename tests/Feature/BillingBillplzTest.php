<?php

namespace Tests\Feature;

use App\Adapters\Billplz\BillplzClient;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\Receipt;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Identity\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingBillplzTest extends TestCase
{
    use RefreshDatabase;

    private const SANDBOX_SIG = 'S-sandbox-signature-key';
    private const PROD_SIG = 'S-production-signature-key';

    public function test_default_mode_is_sandbox_and_keys_are_encrypted_and_masked(): void
    {
        $admin = $this->admin();
        $this->assertSame('SANDBOX', BillingSetting::current()->active_mode);

        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.credentials', 'sandbox'), [
            'api_key' => 'sandbox-api-secret-1234', 'collection_id' => 'col_sb', 'x_signature_key' => self::SANDBOX_SIG,
        ])->assertRedirect(route('admin.billing.settings'));

        $raw = DB::table('billing_settings')->first();
        $this->assertNotSame('sandbox-api-secret-1234', $raw->sandbox_api_key);
        $this->assertSame('sandbox-api-secret-1234', BillingSetting::current()->sandbox_api_key);

        $this->actingAs($admin, 'admin')->get(route('admin.billing.settings'))
            ->assertOk()->assertSee('••••1234')->assertDontSee('sandbox-api-secret-1234');

        // Medan kosong mengekalkan nilai lama
        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.credentials', 'sandbox'), ['api_key' => '', 'collection_id' => '', 'x_signature_key' => '']);
        $this->assertSame('col_sb', BillingSetting::current()->sandbox_collection_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'BILLPLZ_CREDENTIALS_UPDATED']);
    }

    public function test_production_requires_complete_keys_confirmation_and_reason_and_is_audited(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.mode'), ['mode' => 'PRODUCTION', 'confirm' => '1', 'reason' => 'Go live'])
            ->assertSessionHasErrors('mode');
        $this->assertSame('SANDBOX', BillingSetting::current()->active_mode);

        $this->configure();
        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.mode'), ['mode' => 'PRODUCTION'])
            ->assertSessionHasErrors(['confirm', 'reason']);

        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.mode'), ['mode' => 'PRODUCTION', 'confirm' => '1', 'reason' => 'Go live'])
            ->assertRedirect(route('admin.billing.settings'));
        $this->assertSame('PRODUCTION', BillingSetting::current()->active_mode);
        $this->assertDatabaseHas('audit_logs', ['action' => 'BILLING_MODE_CHANGED', 'reason' => 'Go live', 'actor_id' => $admin->id]);
    }

    public function test_sandbox_test_flow_creates_test_invoice_and_redirects_to_billplz_sandbox(): void
    {
        $this->configure();
        Http::fake(['www.billplz-sandbox.com/api/v3/bills' => Http::response(['id' => 'sbbill01', 'url' => 'https://www.billplz-sandbox.com/bills/sbbill01'])]);

        $this->actingAs($this->admin(), 'admin')->post(route('admin.billing.test.start'), [
            'name' => 'Penguji', 'email' => 'test@example.test', 'amount' => '12.50', 'type' => 'DEPOSIT',
        ])->assertRedirect('https://www.billplz-sandbox.com/bills/sbbill01');

        $invoice = Invoice::query()->sole();
        $this->assertTrue($invoice->is_sandbox);
        $this->assertStringStartsWith('TEST-INV-'.now()->year.'-0001', $invoice->number);
        $payment = Payment::query()->sole();
        $this->assertSame(1250, $payment->amount_cents);
        $this->assertSame('SANDBOX', $payment->gateway_mode);

        Http::assertSent(fn ($request) => $request->url() === 'https://www.billplz-sandbox.com/api/v3/bills'
            && $request['amount'] == 1250 && $request['collection_id'] === 'col_sb'
            && $request['callback_url'] === route('billing.billplz.callback')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('sb-key:')));
    }

    public function test_billplz_urls_use_app_url_not_request_host(): void
    {
        $this->configure();
        config(['app.url' => 'https://abc123.ngrok-free.app']);
        Http::fake(['*' => Http::response(['id' => 'b1', 'url' => 'https://www.billplz-sandbox.com/bills/b1'])]);

        $this->actingAs($this->admin(), 'admin')->post(route('admin.billing.invoice.pay', $this->sandboxInvoice()));

        Http::assertSent(fn ($request) => $request['callback_url'] === 'https://abc123.ngrok-free.app/billing/billplz/callback'
            && $request['redirect_url'] === 'https://abc123.ngrok-free.app/billing/billplz/return');
    }

    public function test_test_flow_is_blocked_in_production_mode(): void
    {
        $this->configure();
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        Http::fake();

        $this->actingAs($this->admin(), 'admin')->post(route('admin.billing.test.start'), ['name' => 'X', 'email' => 'x@example.test', 'amount' => '10', 'type' => 'OTHER'])
            ->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('invoices', 0);
        Http::assertNothingSent();
    }

    public function test_valid_callback_marks_paid_creates_one_receipt_and_is_idempotent(): void
    {
        [$invoice, $payment] = $this->pendingPayment();
        $payload = $this->signedCallback($payment, ['paid_amount' => (string) $payment->amount_cents]);

        $this->post(route('billing.billplz.callback'), $payload)->assertOk();
        $this->post(route('billing.billplz.callback'), $payload)->assertOk();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame('25.00', $invoice->fresh()->amount_paid);
        $receipt = Receipt::query()->sole();
        $this->assertStringStartsWith('TEST-RCP-', $receipt->number);
        $this->assertTrue($receipt->is_sandbox);
    }

    public function test_invalid_signature_is_rejected_without_financial_effect(): void
    {
        [$invoice, $payment] = $this->pendingPayment();
        $payload = $this->signedCallback($payment, ['paid_amount' => (string) $payment->amount_cents]);
        $payload['x_signature'] = str_repeat('0', 64);

        $this->post(route('billing.billplz.callback'), $payload)->assertForbidden();

        $payload = $this->signedCallback($payment, ['paid_amount' => (string) $payment->amount_cents], self::PROD_SIG);
        $this->post(route('billing.billplz.callback'), $payload)->assertForbidden();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->fresh()->status);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_amount_mismatch_goes_to_review_required(): void
    {
        [$invoice, $payment] = $this->pendingPayment();
        $payload = $this->signedCallback($payment, ['paid_amount' => '100']);

        $this->post(route('billing.billplz.callback'), $payload)->assertOk();

        $this->assertSame(Payment::STATUS_REVIEW_REQUIRED, $payment->fresh()->status);
        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->fresh()->status);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_unpaid_callback_marks_failed_and_later_paid_callback_succeeds(): void
    {
        [$invoice, $payment] = $this->pendingPayment();

        $this->post(route('billing.billplz.callback'), $this->signedCallback($payment, ['paid' => 'false', 'state' => 'due', 'paid_amount' => '0']))->assertOk();
        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);

        $this->post(route('billing.billplz.callback'), $this->signedCallback($payment, ['paid_amount' => (string) $payment->amount_cents]))->assertOk();
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_unknown_bill_callback_returns_404(): void
    {
        $this->configure();
        $this->post(route('billing.billplz.callback'), ['id' => 'nope', 'x_signature' => 'x'])->assertNotFound();
    }

    public function test_browser_return_is_not_proof_of_payment(): void
    {
        [$invoice, $payment] = $this->pendingPayment();
        $params = ['id' => $payment->gateway_bill_id, 'paid' => 'true', 'paid_at' => '2026-09-24 10:00:00 +0800'];
        $params['x_signature'] = app(BillplzClient::class)->redirectSignature($params, self::SANDBOX_SIG);

        $this->get(route('billing.billplz.return').'?'.http_build_query(['billplz' => $params]))
            ->assertOk()->assertSee('Bayaran sedang disahkan');

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->fresh()->status);

        $params['x_signature'] = 'bad';
        $this->get(route('billing.billplz.return').'?'.http_build_query(['billplz' => $params]))->assertOk()->assertSee('tidak dapat disahkan');
    }

    public function test_double_click_reuses_pending_bill(): void
    {
        $this->configure();
        Http::fake(['*' => Http::response(['id' => 'bill-once', 'url' => 'https://www.billplz-sandbox.com/bills/bill-once'])]);
        $invoice = $this->sandboxInvoice();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.billing.invoice.pay', $invoice))->assertRedirect('https://www.billplz-sandbox.com/bills/bill-once');
        $this->actingAs($admin, 'admin')->post(route('admin.billing.invoice.pay', $invoice))->assertRedirect('https://www.billplz-sandbox.com/bills/bill-once');

        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    public function test_billplz_api_failure_marks_payment_failed(): void
    {
        $this->configure();
        Http::fake(['*' => Http::response(['error' => ['type' => 'Unauthorized', 'message' => 'Invalid key']], 401)]);
        $invoice = $this->sandboxInvoice();

        $this->actingAs($this->admin(), 'admin')->post(route('admin.billing.invoice.pay', $invoice))
            ->assertRedirect(route('admin.billing.invoice', $invoice))->assertSessionHasErrors('payment');
        $this->assertSame(Payment::STATUS_FAILED, Payment::query()->sole()->status);
    }

    public function test_sandbox_invoice_cannot_be_paid_after_switching_to_production(): void
    {
        $this->configure();
        $invoice = $this->sandboxInvoice();
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        Http::fake();

        $this->actingAs($this->admin(), 'admin')->post(route('admin.billing.invoice.pay', $invoice))->assertSessionHasErrors('payment');
        Http::assertNothingSent();
    }

    public function test_production_invoice_uses_nat_numbering_and_production_scope_excludes_sandbox(): void
    {
        $this->configure();
        $this->sandboxInvoice();
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        $production = app(InvoiceService::class)->issue('DEPOSIT', ['name' => 'Pelanggan', 'email' => 'p@example.test'], [['description' => 'Deposit', 'quantity' => 1, 'unit_price' => '500']]);

        $this->assertSame('NAT-INV-'.now()->year.'-0001', $production->number);
        $this->assertFalse($production->is_sandbox);
        $this->assertSame([$production->id], Invoice::query()->production()->pluck('id')->all());
    }

    public function test_purge_removes_only_sandbox_records(): void
    {
        [$sandboxInvoice, $sandboxPayment] = $this->pendingPayment();
        $this->post(route('billing.billplz.callback'), $this->signedCallback($sandboxPayment, ['paid_amount' => (string) $sandboxPayment->amount_cents]));
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        $production = app(InvoiceService::class)->issue('DEPOSIT', ['name' => 'P', 'email' => 'p@example.test'], [['description' => 'Deposit', 'quantity' => 1, 'unit_price' => '500']]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.billing.sandbox.purge'))->assertSessionHasErrors('confirm');
        $this->actingAs($admin, 'admin')->post(route('admin.billing.sandbox.purge'), ['confirm' => '1'])->assertRedirect();

        $this->assertSame([$production->id], Invoice::query()->pluck('id')->all());
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('receipts', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SANDBOX_BILLING_PURGED']);
    }

    public function test_admin_billing_pages_render(): void
    {
        [$invoice] = $this->pendingPayment();
        $admin = $this->admin();

        foreach ([route('admin.dashboard'), route('admin.billing.settings'), route('admin.billing.test'), route('admin.billing.invoices', ['mode' => 'sandbox']), route('admin.billing.payments', ['mode' => 'sandbox']), route('admin.billing.invoice', $invoice)] as $url) {
            $this->actingAs($admin, 'admin')->get($url)->assertOk()->assertSee('Mod pembayaran');
        }
        $this->actingAs($admin, 'admin')->get(route('admin.billing.invoices'))->assertOk()->assertDontSee($invoice->number);
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin'.Admin::query()->count().'@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }

    private function configure(): void
    {
        BillingSetting::current()->forceFill([
            'sandbox_api_key' => 'sb-key', 'sandbox_collection_id' => 'col_sb', 'sandbox_x_signature_key' => self::SANDBOX_SIG,
            'production_api_key' => 'prod-key', 'production_collection_id' => 'col_prod', 'production_x_signature_key' => self::PROD_SIG,
        ])->save();
    }

    private function sandboxInvoice(): Invoice
    {
        return app(InvoiceService::class)->issue('DEPOSIT', ['name' => 'Penguji', 'email' => 'test@example.test'], [['description' => 'Ujian', 'quantity' => 1, 'unit_price' => '25.00']]);
    }

    /** @return array{0: Invoice, 1: Payment} */
    private function pendingPayment(): array
    {
        $this->configure();
        $invoice = $this->sandboxInvoice();
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id, 'is_sandbox' => true, 'gateway' => 'BILLPLZ', 'gateway_mode' => 'SANDBOX',
            'gateway_bill_id' => 'bill'.$invoice->id, 'gateway_url' => 'https://www.billplz-sandbox.com/bills/bill'.$invoice->id,
            'gateway_collection_id' => 'col_sb', 'amount_cents' => 2500, 'status' => Payment::STATUS_PENDING,
        ]);

        return [$invoice, $payment];
    }

    private function signedCallback(Payment $payment, array $overrides = [], string $key = self::SANDBOX_SIG): array
    {
        $payload = array_merge([
            'id' => $payment->gateway_bill_id, 'collection_id' => 'col_sb', 'paid' => 'true', 'state' => 'paid',
            'amount' => (string) $payment->amount_cents, 'paid_amount' => (string) $payment->amount_cents, 'due_at' => '2026-9-24',
            'email' => 'test@example.test', 'mobile' => '', 'name' => 'PENGUJI', 'url' => $payment->gateway_url,
            'paid_at' => '2026-09-24 10:00:00 +0800',
        ], $overrides);
        $payload['x_signature'] = app(BillplzClient::class)->callbackSignature($payload, $key);

        return $payload;
    }
}
