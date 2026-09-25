<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Affiliate\Services\AffiliateCustomerService;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\InvoiceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCustomersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_sees_own_customers_masked_with_bills_and_commission_status(): void
    {
        $affiliate = $this->affiliate('siti');
        $customer = User::factory()->create(['name' => 'Aminah Binti Ahmad', 'email' => 'aminah@example.test', 'phone' => '0199999999']);
        AffiliateClientLink::query()->create(['affiliate_id' => $affiliate->id, 'customer_user_id' => $customer->id, 'linked_at' => now()]);

        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        $deposit = $this->invoice($customer, '5000');
        $final = $this->invoice($customer, '5000');
        $deposit->forceFill(['status' => Invoice::STATUS_PAID, 'amount_paid' => '5000'])->save();
        AffiliateCommission::query()->where('invoice_id', $deposit->id)->update(['status' => 'RELEASED', 'released_at' => now()]);

        BillingSetting::current()->forceFill(['active_mode' => 'SANDBOX'])->save();
        $sandbox = $this->invoice($customer, '100');

        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.customers'))
            ->assertOk()
            ->assertSee('Aminah B*** A***')
            ->assertDontSee('Aminah Binti Ahmad')
            ->assertDontSee('aminah@example.test')
            ->assertDontSee('0199999999')
            ->assertSee($deposit->number)->assertSee($final->number)->assertDontSee($sandbox->number)
            ->assertSee('Dibayar')->assertSee('Belum dibayar')
            ->assertSee('350.00')->assertSee('200.00')
            ->assertSee('Released')->assertSee('Pending')
            ->assertSee('RM 10,000.00')->assertSee('RM 550.00');
    }

    public function test_affiliate_cannot_see_other_affiliates_customers(): void
    {
        $mine = $this->affiliate('mine');
        $other = $this->affiliate('other');
        $customer = User::factory()->create(['name' => 'Rahsia Orang']);
        AffiliateClientLink::query()->create(['affiliate_id' => $other->id, 'customer_user_id' => $customer->id, 'linked_at' => now()]);
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        $invoice = $this->invoice($customer, '1000');

        $this->actingAs($mine, 'affiliate')->get(route('affiliate.customers'))
            ->assertOk()->assertDontSee('Rahsia')->assertDontSee($invoice->number)->assertSee('Belum ada pelanggan');
    }

    public function test_page_requires_complete_profile(): void
    {
        $incomplete = Affiliate::query()->create(['name' => 'Baru', 'email' => 'baru@example.test', 'phone' => '012']);

        $this->actingAs($incomplete, 'affiliate')->get(route('affiliate.customers'))->assertRedirect(route('affiliate.profile'));
        $this->app['auth']->forgetGuards();
        $this->get(route('affiliate.customers'))->assertRedirect(route('affiliate.login'));
    }

    public function test_name_masking(): void
    {
        $this->assertSame('Siti A***', AffiliateCustomerService::maskName('Siti Aminah'));
        $this->assertSame('Ami***', AffiliateCustomerService::maskName('Aminah'));
        $this->assertSame('Pelanggan', AffiliateCustomerService::maskName('  '));
    }

    private function affiliate(string $username): Affiliate
    {
        $affiliate = Affiliate::query()->create(['name' => ucfirst($username), 'email' => $username.'@example.test', 'phone' => '012', 'username' => $username]);
        $affiliate->forceFill(['email_verified_at' => now(), 'profile_completed_at' => now()])->save();

        return $affiliate;
    }

    private function invoice(User $customer, string $amount): Invoice
    {
        return app(InvoiceService::class)->issue('DEPOSIT', ['name' => $customer->name, 'email' => $customer->email], [['description' => 'Projek', 'quantity' => 1, 'unit_price' => $amount]], $customer);
    }
}
