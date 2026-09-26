<?php

namespace Tests\Feature;

use App\Adapters\Billplz\BillplzClient;
use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Identity\Models\Admin;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Engines\Partnership\Models\PartnerEarning;
use App\Engines\Partnership\Models\PartnerPoolEntry;
use App\Engines\Partnership\Models\PartnerSetting;
use App\Engines\Partnership\Services\PartnerPoolService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PartnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION', 'production_api_key' => 'k', 'production_collection_id' => 'col', 'production_x_signature_key' => 'S-prod'])->save();
    }

    public function test_registration_closed_until_admin_enables_program(): void
    {
        $this->get(route('partner.register'))->assertOk()->assertSee('Program Partnership belum dibuka.');
        $this->post(route('partner.register.send'), $this->form())->assertNotFound();
        $this->get('/register')->assertSee('Pendaftaran Partnership belum diaktifkan.')->assertDontSee('nvestor');

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.partners.settings'), ['pool_percent' => 10, 'min_capital' => 5000, 'max_total_capital' => 100000, 'program_enabled' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue(PartnerSetting::current()->program_enabled);
        $this->get('/register')->assertSee('Daftar Partnership');
    }

    public function test_partnership_flow_terms_register_pay_complete_profile_dashboard(): void
    {
        PartnerSetting::current()->update(['program_enabled' => true]);
        $admin = $this->admin();

        // Langkah 1–2: halaman daftar papar terma; butang Daftar bergantung pada tanda setuju
        $this->get(route('partner.register'))->assertOk()->assertSee('Terma &amp; syarat', false)->assertSee(config('partnership_terms.terms')[0]['title'])->assertSee(':disabled="! agree"', false)
            // Langkah 1 (premium): modal minimum daripada tetapan, foto & sokongan syarikat.
            ->assertSee('RM5,000')->assertSee('Sokongan penuh daripada pihak syarikat')->assertSee('images/partnership/partnership-hero.webp')
            // Langkah 2 (premium): terma Owner v1.0 lengkap + panel "Kenapa perlu baca terma ini?".
            ->assertSee('Terma dan Syarat Partnership')->assertSee('(versi 1.0)', false)->assertSee('Kenapa perlu baca terma ini?')->assertSee('images/partnership/partnership-terms.webp')
            ->assertSee('mengumpul modal RM100,000')->assertSee('selama 2 tahun dari tarikh daftar')->assertSee('Pengeluaran keuntungan');
        $this->assertCount(9, config('partnership_terms.terms'));
        foreach (['partnership-hero.webp', 'partnership-terms.webp'] as $img) {
            $this->assertFileExists(public_path('images/partnership/'.$img));
        }

        // Langkah 3: persetujuan terma disemak di pelayan; modal minimum RM5,000
        $this->post(route('partner.register.send'), array_merge($this->form(), ['agree' => '']))->assertSessionHasErrors('agree');
        $this->post(route('partner.register.send'), array_merge($this->form(), ['amount' => 4999]))->assertSessionHasErrors('amount');
        $this->post(route('partner.register.send'), $this->form())->assertRedirect(route('partner.register'));
        $this->get(route('partner.register'))->assertSee('Sahkan emel anda')->assertSee('RM 6,000.00');
        EmailOtpChallenge::query()->where('purpose', 'PARTNER_LOGIN')->update(['code_hash' => Hash::make('123456')]);
        $this->post(route('partner.register.verify'), ['code' => '123456'])->assertRedirect(route('partner.onboarding'));

        $a = Partner::query()->sole();
        $this->assertSame(Partner::APPROVED, $a->status);
        $this->assertNotNull($a->terms_accepted_at);
        $this->assertNull($a->id_number);
        $capA = PartnerCapital::query()->sole();
        $invoice = $capA->invoice;
        $this->assertSame('6000.00', $capA->amount);
        $this->assertSame('PARTNER_CAPITAL', $invoice->type);
        $this->assertNull($invoice->customer_user_id);
        $this->assertDatabaseCount('affiliate_commissions', 0);

        // Belum bayar → dashboard & menu disekat, kekal di langkah bayaran
        $this->get(route('partner.dashboard'))->assertRedirect(route('partner.onboarding'));
        $this->get(route('partner.returns'))->assertRedirect(route('partner.onboarding'));
        $this->get(route('partner.onboarding'))->assertOk()->assertSee('Bayaran modal')->assertSee('Bayar RM 6,000.00');
        $this->post(route('partner.profile.complete'), $this->profile())->assertSessionHasErrors('profile');

        // Bayaran berjaya melalui callback Billplz → modal aktif → langkah 4 (lengkapkan profil)
        Http::fake(['www.billplz.com/api/v3/bills' => Http::sequence()->push(['id' => 'capbill', 'url' => 'https://www.billplz.com/bills/capbill'])->push(['id' => 'salebill', 'url' => 'https://www.billplz.com/bills/salebill'])]);
        $this->post(route('partner.pay', $invoice))->assertRedirect('https://www.billplz.com/bills/capbill');
        $this->billplzCallback('capbill', 600000);
        $this->assertSame(PartnerCapital::ACTIVE, $capA->fresh()->status);
        $this->assertDatabaseCount('partner_pool_entries', 0);
        $this->get(route('partner.dashboard'))->assertRedirect(route('partner.onboarding'));
        $this->get(route('partner.onboarding'))->assertSee('Lengkapkan profil');

        // Langkah 4 → 5
        $this->post(route('partner.profile.complete'), array_merge($this->profile(), ['bank_account_number' => 'abc']))->assertSessionHasErrors('bank_account_number');
        $this->post(route('partner.profile.complete'), $this->profile())->assertRedirect(route('partner.dashboard'));
        $a->refresh();
        $this->assertNotNull($a->profile_completed_at);
        $this->assertStringNotContainsString('900101145678', (string) DB::table('partners')->value('id_number'));
        $this->assertSame('5678', $a->id_last4);
        $this->assertSame('4455', $a->bank_account_last4);
        $this->get(route('partner.dashboard'))->assertOk()->assertSee('Hai, Ali Partner');
        $this->get(route('partner.onboarding'))->assertRedirect(route('partner.dashboard'));
        $this->actingAs($admin, 'admin')->get(route('admin.partners.show', $a))->assertSee('900101145678');

        // Partner B: modal 4000 (min diturunkan) → nisbah 60:40
        PartnerSetting::current()->update(['min_capital' => 1000]);
        $b = Partner::query()->create(['name' => 'B', 'email' => 'b@example.test', 'phone' => '1', 'id_type' => 'IC', 'id_number' => '1', 'status' => Partner::APPROVED]);
        PartnerCapital::query()->create(['number' => 'NAT-PTC-X', 'partner_id' => $b->id, 'amount' => '4000', 'status' => PartnerCapital::ACTIVE, 'terms_snapshot' => [], 'acceptance_metadata' => [], 'activated_at' => now()]);

        // Jualan pelanggan RM1,000.01 dibayar → pool 10% = 100.00 → A 60.00, B 40.00
        $client = User::factory()->create();
        $sale = app(InvoiceService::class)->issue('DEPOSIT', ['name' => $client->name, 'email' => $client->email], [['description' => 'Projek', 'quantity' => 1, 'unit_price' => '1000.01']], $client);
        $this->actingAs($client, 'client')->post(route('client.billing.pay', $sale));
        $this->billplzCallback('salebill', 100001);
        $this->billplzCallback('salebill', 100001);

        $entry = PartnerPoolEntry::query()->sole();
        $this->assertSame('100.00', $entry->pool_amount);
        $this->assertSame('100.00', $entry->allocated_amount);
        $this->assertSame('60.00', PartnerEarning::query()->where('partner_id', $a->id)->value('amount'));
        $this->assertSame('40.00', PartnerEarning::query()->where('partner_id', $b->id)->value('amount'));
        $this->actingAs($a->fresh(), 'partner')->get(route('partner.dashboard'))->assertSee('RM 60.00')->assertSee('60%');
        $this->actingAs($a->fresh(), 'partner')->get(route('partner.returns'))->assertSee($sale->number);

        // Pengeluaran oleh admin
        $this->actingAs($admin, 'admin')->post(route('admin.partners.payout', $a), ['amount' => '60.01', 'transfer_reference' => 'X', 'confirm' => '1'])->assertSessionHasErrors('amount');
        $this->actingAs($admin, 'admin')->post(route('admin.partners.payout', $a), ['amount' => '50', 'transfer_reference' => 'MBB999', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(1000, $a->fresh()->balanceCents());
        $this->actingAs($admin, 'admin')->get(route('admin.partners.pool'))->assertOk()->assertSee($sale->number);
    }

    public function test_pool_split_is_exact_and_refund_reverses_proportionally(): void
    {
        $pool = app(PartnerPoolService::class);
        $this->assertSame([1 => 34, 2 => 33, 3 => 33], $pool->split(100, [1 => 1, 2 => 1, 3 => 1]));
        $this->assertSame(0, array_sum($pool->split(0, [1 => 5])));

        $p = Partner::query()->create(['name' => 'P', 'email' => 'p@example.test', 'phone' => '1', 'id_type' => 'IC', 'id_number' => '1', 'status' => Partner::APPROVED]);
        PartnerCapital::query()->create(['number' => 'NAT-PTC-Y', 'partner_id' => $p->id, 'amount' => '5000', 'status' => PartnerCapital::ACTIVE, 'terms_snapshot' => [], 'acceptance_metadata' => [], 'activated_at' => now()]);
        $client = User::factory()->create();
        $sale = app(InvoiceService::class)->issue('DEPOSIT', ['name' => 'c', 'email' => 'c@x.test'], [['description' => 'x', 'quantity' => 1, 'unit_price' => '2000']], $client);
        $sale->forceFill(['status' => 'PAID', 'amount_paid' => '2000'])->save();
        $pool->allocateForInvoice($sale->fresh());
        $refund = \App\Engines\Billing\Models\Refund::query()->create(['number' => 'NAT-RF-T', 'invoice_id' => $sale->id, 'amount' => '500', 'reason' => 'x', 'transfer_reference' => 'x', 'refunded_by_admin_id' => $this->admin()->id, 'refunded_at' => now()]);
        $pool->reverseForRefund($refund);
        $pool->reverseForRefund($refund);

        $this->assertSame(15000, $p->fresh()->earningsCents()); // 200 − 50
        $this->assertSame(2, PartnerPoolEntry::query()->count());
    }

    public function test_admin_impersonation_is_readonly(): void
    {
        $admin = $this->admin();
        $client = $this->portalReadyClient();
        $affiliate = Affiliate::query()->create(['name' => 'Aff', 'email' => 'aff@example.test', 'phone' => '012']);
        $partner = Partner::query()->create(['name' => 'Rakan', 'email' => 'r@example.test', 'phone' => '1', 'id_type' => 'IC', 'id_number' => '1', 'status' => Partner::APPROVED, 'profile_completed_at' => now()]);
        PartnerCapital::query()->create(['number' => 'NAT-PTC-Z', 'partner_id' => $partner->id, 'amount' => '5000', 'status' => PartnerCapital::ACTIVE, 'terms_snapshot' => [], 'acceptance_metadata' => [], 'activated_at' => now()]);

        // Tanpa admin → tidak boleh
        $this->post(route('admin.impersonate.start', ['client', $client->id]))->assertRedirect(route('admin.login'));

        $this->actingAs($admin, 'admin')->get(route('admin.clients.index'))->assertOk()->assertSee('Log masuk sebagai');
        $this->actingAs($admin, 'admin')->from(route('admin.clients.index'))->post(route('admin.impersonate.start', ['client', $client->id]))->assertRedirect(route('client.dashboard'));
        $this->get(route('client.dashboard'))->assertOk()->assertSee('MOD ADMIN')->assertSee($client->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'IMPERSONATION_STARTED']);

        // Keputusan Owner 25 Sep 2026 #5: readonly sahaja — SEMUA tindakan bukan-GET disekat, termasuk kemas kini profil biasa.
        $this->from(route('client.profile'))->put(route('client.profile.update'), ['name' => 'Nama Oleh Admin', 'phone' => '0199'])->assertSessionHasErrors('impersonation');
        $this->assertNotSame('Nama Oleh Admin', $client->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'IMPERSONATED_ACTION_BLOCKED']);

        // Bayaran turut disekat
        $inv = app(InvoiceService::class)->issue('OTHER', ['name' => 'c', 'email' => 'c@x.test'], [['description' => 'x', 'quantity' => 1, 'unit_price' => '10']], $client);
        $this->from(route('client.billing.invoice', $inv))->post(route('client.billing.pay', $inv))->assertSessionHasErrors('impersonation');
        $this->assertDatabaseCount('payments', 0);

        // Logout pengguna semasa mod admin → keluar mod admin sahaja
        $this->post(route('client.logout'))->assertRedirect(route('admin.impersonate.stop.get'));
        $this->get(route('admin.impersonate.stop.get'))->assertRedirect();
        $this->assertFalse(auth('client')->check());
        $this->assertTrue(auth('admin')->check());
        $this->assertDatabaseHas('audit_logs', ['action' => 'IMPERSONATION_ENDED']);

        // Affiliate & Partner
        $this->post(route('admin.impersonate.start', ['affiliate', $affiliate->id]))->assertRedirect(route('affiliate.dashboard'));
        $this->get(route('affiliate.profile'))->assertOk()->assertSee('MOD ADMIN');
        $this->post(route('admin.impersonate.stop'));
        $this->post(route('admin.impersonate.start', ['partner', $partner->id]))->assertRedirect(route('partner.dashboard'));
        $this->get(route('partner.dashboard'))->assertOk()->assertSee('MOD ADMIN')->assertSee('Rakan');
        PartnerSetting::current()->update(['program_enabled' => true]);
        $this->post(route('partner.capital.store'), ['amount' => 6000, 'agree' => '1'])->assertSessionHasErrors('impersonation');
        $this->assertDatabaseCount('partner_capitals', 1);

        // Sesi admin tamat → mod admin tamat dan sesi pengguna ditutup
        auth('admin')->logout();
        $this->get(route('partner.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('partner.dashboard'))->assertRedirect(route('partner.login'));
    }

    private function billplzCallback(string $billId, int $cents): void
    {
        $payment = Payment::query()->where('gateway_bill_id', $billId)->sole();
        $payload = ['id' => $billId, 'collection_id' => 'col', 'paid' => 'true', 'state' => 'paid', 'amount' => (string) $cents, 'paid_amount' => (string) $cents,
            'due_at' => '2026-9-24', 'email' => 'x@example.test', 'mobile' => '', 'name' => 'X', 'url' => $payment->gateway_url, 'paid_at' => '2026-09-24 10:00:00 +0800'];
        $payload['x_signature'] = app(BillplzClient::class)->callbackSignature($payload, 'S-prod');
        $this->post(route('billing.billplz.callback'), $payload)->assertOk();
    }

    private function form(): array
    {
        return ['name' => 'Ali Partner', 'email' => 'ali@example.test', 'phone' => '0123', 'id_type' => 'IC', 'amount' => 6000, 'agree' => '1'];
    }

    private function profile(): array
    {
        return ['phone' => '0123', 'id_number' => '900101145678', 'bank_name' => 'Maybank', 'bank_account_holder' => 'Ali Partner', 'bank_account_number' => '1122334455'];
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'admin@example.test'], ['name' => 'Nat', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
