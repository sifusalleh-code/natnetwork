<?php

namespace Tests\Feature;

use App\Adapters\Billplz\BillplzClient;
use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Affiliate\Models\AffiliateRateVersion;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Engines\Affiliate\Services\CommissionCalculator;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Identity\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_matches_spec_examples(): void
    {
        $calc = app(CommissionCalculator::class);
        $tiers = AffiliateRateVersion::current()->tiers;

        foreach ([300 => 30, 500 => 50, 1000 => 90, 2000 => 170, 5000 => 350, 10000 => 550, 20000 => 850] as $sale => $expected) {
            $this->assertSame($expected * 100, $calc->totalCents($tiers, $sale * 100), "RM$sale");
        }

        // Contoh spec 1.3: RM300 → RM30, RM1,700 → RM140, RM3,000 → RM180
        $this->assertSame(3000, $calc->forPurchase($tiers, 0, 30000)['amount_cents']);
        $this->assertSame(14000, $calc->forPurchase($tiers, 30000, 170000)['amount_cents']);
        $this->assertSame(18000, $calc->forPurchase($tiers, 200000, 300000)['amount_cents']);
    }

    public function test_cumulative_purchases_create_pending_commissions_per_spec_example(): void
    {
        [$customer, $affiliate] = $this->linkedCustomer();

        $amounts = [];
        foreach (['300', '1700', '3000'] as $gross) {
            $invoice = $this->productionInvoice($customer, $gross);
            $amounts[] = AffiliateCommission::query()->where('invoice_id', $invoice->id)->value('amount');
        }

        $this->assertSame(['30.00', '140.00', '180.00'], $amounts);
        $this->assertSame(3, AffiliateCommission::query()->where('affiliate_id', $affiliate->id)->where('status', 'PENDING')->count());
        $this->assertSame('5000.00', AffiliateCommission::query()->latest('id')->value('cumulative_after'));
    }

    public function test_invoice_payment_alone_keeps_commission_pending(): void
    {
        [$customer] = $this->linkedCustomer();
        $invoice = $this->productionInvoice($customer, '500');
        $payment = $this->pendingPayment($invoice, 'PRODUCTION');
        $payload = $this->signedCallback($payment);

        $this->post(route('billing.billplz.callback'), $payload)->assertOk();
        $this->post(route('billing.billplz.callback'), $payload)->assertOk();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $commission = AffiliateCommission::query()->sole();
        $this->assertSame(AffiliateCommission::STATUS_PENDING, $commission->status);
        $this->assertSame('50.00', $commission->amount);
        $this->assertNull($commission->released_at);
    }

    public function test_sandbox_invoice_never_creates_commission(): void
    {
        [$customer] = $this->linkedCustomer();
        BillingSetting::current()->forceFill(['active_mode' => 'SANDBOX'])->save();
        $invoice = app(InvoiceService::class)->issue('DEPOSIT', ['name' => 'P', 'email' => 'p@example.test'], [['description' => 'X', 'quantity' => 1, 'unit_price' => '500']], $customer);
        $payment = $this->pendingPayment($invoice, 'SANDBOX');

        $this->post(route('billing.billplz.callback'), $this->signedCallback($payment, 'S-sb'))->assertOk();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertDatabaseCount('affiliate_commissions', 0);
    }

    public function test_customer_without_affiliate_has_no_commission(): void
    {
        $this->configureProduction();
        $this->productionInvoice(User::factory()->create(), '1000');

        $this->assertDatabaseCount('affiliate_commissions', 0);
    }

    public function test_rate_change_applies_to_future_only_and_cumulative_continues(): void
    {
        [$customer] = $this->linkedCustomer();
        $first = $this->productionInvoice($customer, '3000');
        $this->assertSame('230.00', AffiliateCommission::query()->where('invoice_id', $first->id)->value('amount'));

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), [
            'tiers' => [
                ['up_to' => '500', 'rate' => '10'], ['up_to' => '2000', 'rate' => '8'], ['up_to' => '5000', 'rate' => '5'],
                ['up_to' => '10000', 'rate' => '4'], ['up_to' => '', 'rate' => '3'],
            ],
            'confirm' => '1',
        ])->assertRedirect(route('admin.affiliate.settings'));

        $second = $this->productionInvoice($customer, '2000');

        $this->assertSame('230.00', AffiliateCommission::query()->where('invoice_id', $first->id)->value('amount'));
        // Bahagian RM3,000.01–RM5,000 (RM2,000) pada kadar baharu 5% = RM100
        $this->assertSame('100.00', AffiliateCommission::query()->where('invoice_id', $second->id)->value('amount'));
        $this->assertNotSame(
            AffiliateCommission::query()->where('invoice_id', $first->id)->value('affiliate_rate_version_id'),
            AffiliateCommission::query()->where('invoice_id', $second->id)->value('affiliate_rate_version_id'),
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'AFFILIATE_RATES_CHANGED', 'actor_id' => $admin->id]);
    }

    public function test_admin_can_add_and_remove_tiers_with_validation_and_warning(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get(route('admin.affiliate.settings'))
            ->assertOk()->assertSee('Amaran')->assertSee('akan datang')->assertSee('RM350.00');

        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), ['tiers' => [['up_to' => '1000', 'rate' => '10'], ['up_to' => '', 'rate' => '5']]])
            ->assertSessionHasErrors('confirm');
        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), ['tiers' => [['up_to' => '1000', 'rate' => '10'], ['up_to' => '900', 'rate' => '8'], ['up_to' => '', 'rate' => '5']], 'confirm' => '1'])
            ->assertSessionHasErrors('tiers');
        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), ['tiers' => [['up_to' => '1000', 'rate' => '10'], ['up_to' => '5000', 'rate' => '5']], 'confirm' => '1'])
            ->assertSessionHasErrors('tiers');
        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), ['tiers' => [['up_to' => '', 'rate' => '150']], 'confirm' => '1'])
            ->assertSessionHasErrors('tiers.0.rate');

        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), ['tiers' => [
            ['up_to' => '1000', 'rate' => '12'], ['up_to' => '', 'rate' => '5'],
        ], 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertSame([['up_to' => '1000.00', 'rate' => '12.00'], ['up_to' => null, 'rate' => '5.00']], AffiliateRateVersion::current()->tiers);
        $this->assertSame(2, AffiliateRateVersion::query()->count());

        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.rates'), ['tiers' => [
            ['up_to' => '1000', 'rate' => '12'], ['up_to' => '', 'rate' => '5'],
        ], 'confirm' => '1'])->assertSessionHas('status', 'Tiada perubahan pada kadar komisyen.');
        $this->assertSame(2, AffiliateRateVersion::query()->count());
    }

    public function test_admin_can_change_cookie_days(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.cookie'), ['cookie_days' => 0])->assertSessionHasErrors('cookie_days');
        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.settings.cookie'), ['cookie_days' => 45])->assertSessionHasNoErrors();

        $this->assertSame(45, AffiliateSetting::cookieDays());
        $this->assertDatabaseHas('audit_logs', ['action' => 'AFFILIATE_COOKIE_DAYS_CHANGED']);
    }

    public function test_affiliate_settings_requires_admin(): void
    {
        $this->get(route('admin.affiliate.settings'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.affiliate.settings.cookie'), ['cookie_days' => 10])->assertRedirect(route('admin.login'));
        $this->assertSame(30, AffiliateSetting::cookieDays());
    }

    /** @return array{0: User, 1: Affiliate} */
    private function linkedCustomer(): array
    {
        $this->configureProduction();
        $affiliate = Affiliate::query()->create(['name' => 'Aff', 'email' => 'aff@example.test', 'phone' => '012', 'username' => 'aff']);
        $customer = User::factory()->create();
        AffiliateClientLink::query()->create(['affiliate_id' => $affiliate->id, 'customer_user_id' => $customer->id, 'linked_at' => now()]);

        return [$customer, $affiliate];
    }

    private function configureProduction(): void
    {
        BillingSetting::current()->forceFill([
            'active_mode' => 'PRODUCTION',
            'sandbox_api_key' => 'sb', 'sandbox_collection_id' => 'col_sb', 'sandbox_x_signature_key' => 'S-sb',
            'production_api_key' => 'prod', 'production_collection_id' => 'col_prod', 'production_x_signature_key' => 'S-prod',
        ])->save();
    }

    private function productionInvoice(User $customer, string $amount): Invoice
    {
        return app(InvoiceService::class)->issue('DEPOSIT', ['name' => $customer->name, 'email' => $customer->email], [['description' => 'Projek', 'quantity' => 1, 'unit_price' => $amount]], $customer);
    }

    private function pendingPayment(Invoice $invoice, string $mode): Payment
    {
        return Payment::query()->create([
            'invoice_id' => $invoice->id, 'is_sandbox' => $mode === 'SANDBOX', 'gateway_mode' => $mode,
            'gateway_bill_id' => 'bill'.$invoice->id, 'gateway_url' => 'https://example.test/bills/'.$invoice->id,
            'gateway_collection_id' => $mode === 'SANDBOX' ? 'col_sb' : 'col_prod',
            'amount_cents' => $invoice->outstandingCents(), 'status' => Payment::STATUS_PENDING,
        ]);
    }

    private function signedCallback(Payment $payment, string $key = 'S-prod'): array
    {
        $payload = [
            'id' => $payment->gateway_bill_id, 'collection_id' => $payment->gateway_collection_id, 'paid' => 'true', 'state' => 'paid',
            'amount' => (string) $payment->amount_cents, 'paid_amount' => (string) $payment->amount_cents, 'due_at' => '2026-9-24',
            'email' => 'p@example.test', 'mobile' => '', 'name' => 'P', 'url' => $payment->gateway_url, 'paid_at' => '2026-09-24 10:00:00 +0800',
        ];
        $payload['x_signature'] = app(BillplzClient::class)->callbackSignature($payload, $key);

        return $payload;
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
