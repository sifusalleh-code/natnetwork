<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Affiliate\Models\AffiliatePoster;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Engines\Affiliate\Models\AffiliateWithdrawal;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Identity\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliatePortalFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_stats_link_traffic_and_weekly_task(): void
    {
        $affiliate = $this->affiliate('siti');
        $this->clicks($affiliate, 3);

        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.dashboard'))->assertOk()
            ->assertSee('Dashboard')->assertSee('?ref=siti')->assertSee('Trafik 14 hari')
            ->assertSee('Task mingguan')->assertSee('0 / 20')->assertSee('3 / 100')->assertSee('Belum lengkap');
    }

    public function test_admin_posters_and_affiliate_shares_count_toward_task(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.posters.store'), [
            'title' => 'Website Bisnes', 'caption' => 'Bina website anda di sini: {link}', 'image' => UploadedFile::fake()->image('poster.jpg', 1080, 1350),
        ])->assertSessionHasNoErrors();
        $poster = AffiliatePoster::query()->firstOrFail();
        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.posters.store'), ['title' => 'x', 'caption' => 'y', 'image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertSessionHasErrors('image');

        $affiliate = $this->affiliate('siti');
        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.studio-poster'))->assertOk()->assertSee('Website Bisnes')->assertSee('Bina website anda di sini: '.rtrim(config('app.url'), '/').'/?ref=siti');
        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.posters.image', $poster))->assertOk();

        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.posters.share', $poster), ['channel' => 'download'])->assertOk()->assertDownload();
        $this->actingAs($affiliate, 'affiliate')->postJson(route('affiliate.posters.share', $poster), ['channel' => 'copy'])->assertOk();
        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.posters.share', $poster), ['channel' => 'whatsapp'])->assertRedirectContains('https://wa.me/?text=');
        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.posters.share', $poster), ['channel' => 'bogus'])->assertSessionHasErrors('channel');
        $this->assertDatabaseCount('affiliate_shares', 3);

        $this->actingAs($admin, 'admin')->put(route('admin.affiliate.posters.update', $poster), ['title' => 'Website Bisnes', 'caption' => 'c'])->assertSessionHasNoErrors();
        $this->assertFalse($poster->fresh()->is_active);
        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.studio-poster'))->assertDontSee('Bina website anda');
        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.posters.image', $poster))->assertNotFound();
    }

    public function test_withdrawal_requires_weekly_task_balance_and_is_processed_by_admin(): void
    {
        $affiliate = $this->affiliate('siti');
        $this->releasedCommission($affiliate, '5000');
        $available = (float) AffiliateCommission::query()->where('status', 'RELEASED')->sum('amount');
        $this->assertGreaterThan(0, $available);

        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.withdrawals.store'), ['amount' => '10'])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('affiliate_withdrawals', 0);

        AffiliateSetting::query()->firstOrCreate([], ['cookie_days' => 30])->forceFill(['weekly_share_target' => 0, 'weekly_unique_click_target' => 2])->save();
        $this->clicks($affiliate, 2);

        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.withdrawals.store'), ['amount' => (string) ($available + 1)])->assertSessionHasErrors('amount');
        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.withdrawals.store'), ['amount' => '100.00'])->assertSessionHasNoErrors()->assertRedirect(route('affiliate.wallet'));
        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.withdrawals.store'), ['amount' => '1'])->assertSessionHasErrors('amount');

        $w = AffiliateWithdrawal::query()->firstOrFail();
        $this->assertSame('REQUESTED', $w->status);
        $this->assertSame('1234567890', $w->bank_account_number);
        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.wallet'))->assertOk()->assertSee('Dalam proses')->assertSee('RM'.number_format($available - 100, 2));

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get(route('admin.affiliate.withdrawals'))->assertOk()->assertSee('1234567890')->assertSee('RM100.00');
        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.withdrawals.paid', $w), ['reference' => 'TRX-1'])->assertSessionHasErrors('confirm');
        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.withdrawals.paid', $w), ['reference' => 'TRX-1', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.withdrawals.reject', $w), ['reason' => 'x'])->assertSessionHasErrors('withdrawal');
        $this->assertSame('PAID', $w->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'AFFILIATE_WITHDRAWAL_PAID']);

        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.withdrawals.store'), ['amount' => '50'])->assertSessionHasNoErrors();
        $second = AffiliateWithdrawal::query()->latest('id')->firstOrFail();
        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.withdrawals.reject', $second), ['reason' => 'Nama bank tidak sepadan'])->assertSessionHasNoErrors();
        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.wallet'))->assertSee('Ditolak')->assertSee('RM'.number_format($available - 100, 2));
    }

    public function test_other_affiliate_and_guests_cannot_access(): void
    {
        $this->get(route('affiliate.wallet'))->assertRedirect();
        $this->get(route('admin.affiliate.withdrawals'))->assertRedirect();
    }

    private function affiliate(string $username): Affiliate
    {
        $affiliate = Affiliate::query()->create(['name' => ucfirst($username), 'email' => $username.'@example.test', 'phone' => '012', 'username' => $username, 'bank_name' => 'Maybank', 'bank_account_number' => '1234567890', 'bank_account_last4' => '7890']);
        $affiliate->forceFill(['email_verified_at' => now(), 'profile_completed_at' => now()])->save();

        return $affiliate;
    }

    private function clicks(Affiliate $affiliate, int $visitors): void
    {
        foreach (range(1, $visitors) as $i) {
            AffiliateClick::query()->create(['affiliate_id' => $affiliate->id, 'visitor_token' => (string) Str::uuid(), 'source' => 'direct', 'device_type' => 'desktop', 'landing_path' => '/', 'clicked_at' => now()]);
        }
    }

    private function releasedCommission(Affiliate $affiliate, string $amount): void
    {
        $customer = User::factory()->create();
        AffiliateClientLink::query()->create(['affiliate_id' => $affiliate->id, 'customer_user_id' => $customer->id, 'linked_at' => now()]);
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION'])->save();
        $invoice = app(InvoiceService::class)->issue('DEPOSIT', ['name' => $customer->name, 'email' => $customer->email], [['description' => 'Projek', 'quantity' => 1, 'unit_price' => $amount]], $customer);
        AffiliateCommission::query()->where('invoice_id', $invoice->id)->update(['status' => 'RELEASED', 'released_at' => now()]);
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
