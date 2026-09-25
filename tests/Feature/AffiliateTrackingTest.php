<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Engines\Affiliate\Services\AffiliateTrackingService;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Sales\Models\BuilderSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AffiliateTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_link_records_click_source_device_and_sets_cookie(): void
    {
        $this->seed();
        $affiliate = $this->completeAffiliate('siti');

        $response = $this->withHeaders(['referer' => 'https://l.facebook.com/l.php', 'User-Agent' => 'Mozilla/5.0 (iPhone; Mobile)'])
            ->get('/?ref=siti');

        $response->assertOk()->assertCookie(AffiliateTrackingService::REFERRAL_COOKIE)->assertCookie(AffiliateTrackingService::VISITOR_COOKIE);
        $click = AffiliateClick::query()->sole();
        $this->assertSame($affiliate->id, $click->affiliate_id);
        $this->assertSame('facebook', $click->source);
        $this->assertSame('mobile', $click->device_type);
        $this->assertSame('/', $click->landing_path);
    }

    public function test_unknown_or_incomplete_affiliate_is_ignored(): void
    {
        $this->seed();
        Affiliate::query()->create(['name' => 'Baru', 'email' => 'baru@example.test', 'phone' => '012', 'username' => 'baru']);

        $this->get('/?ref=baru')->assertOk()->assertCookieMissing(AffiliateTrackingService::REFERRAL_COOKIE);
        $this->get('/?ref=tiada')->assertOk();
        $this->assertDatabaseCount('affiliate_clicks', 0);
    }

    public function test_new_customer_is_linked_to_the_last_clicked_affiliate(): void
    {
        $this->seed();
        $first = $this->completeAffiliate('pertama');
        $last = $this->completeAffiliate('terakhir');
        AffiliateClick::query()->create($this->clickData($first));
        $lastClick = AffiliateClick::query()->create($this->clickData($last));

        $this->verifyBuilderCustomer('baru@example.test', (string) $lastClick->id);

        $customer = User::query()->where('email', 'baru@example.test')->firstOrFail();
        $link = AffiliateClientLink::query()->sole();
        $this->assertSame($last->id, $link->affiliate_id);
        $this->assertSame($customer->id, $link->customer_user_id);
        $this->assertSame($lastClick->id, $link->affiliate_click_id);
    }

    public function test_existing_customer_is_not_linked(): void
    {
        $this->seed();
        $affiliate = $this->completeAffiliate('siti');
        $click = AffiliateClick::query()->create($this->clickData($affiliate));
        User::factory()->create(['email' => 'lama@example.test']);

        $this->verifyBuilderCustomer('lama@example.test', (string) $click->id);

        $this->assertDatabaseCount('affiliate_client_links', 0);
    }

    public function test_expired_cookie_does_not_link_customer(): void
    {
        $this->seed();
        AffiliateSetting::query()->update(['cookie_days' => 30]);
        $affiliate = $this->completeAffiliate('siti');
        $click = AffiliateClick::query()->create(array_merge($this->clickData($affiliate), ['clicked_at' => now()->subDays(31)]));

        $this->verifyBuilderCustomer('baru@example.test', (string) $click->id);

        $this->assertDatabaseCount('affiliate_client_links', 0);
        $this->assertDatabaseHas('users', ['email' => 'baru@example.test']);
    }

    private function verifyBuilderCustomer(string $email, string $clickId): void
    {
        $builder = BuilderSession::query()->create([
            'entry_path' => 'GUIDED', 'contact_name' => 'Pelanggan', 'contact_email' => $email, 'contact_phone' => '0123456789',
        ]);
        EmailOtpChallenge::query()->create([
            'email' => $email, 'purpose' => EmailOtpChallenge::PURPOSE_CLIENT_LOGIN,
            'code_hash' => Hash::make('123456'), 'attempts' => 0, 'sent_at' => now(), 'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession(['natnetwork_builder_session_id' => $builder->id])
            ->withCookie(AffiliateTrackingService::REFERRAL_COOKIE, $clickId)
            ->post(route('builder.verify'), ['code' => '123456'])
            ->assertRedirect(route('builder.start'));
    }

    private function completeAffiliate(string $username): Affiliate
    {
        $affiliate = Affiliate::query()->create(['name' => ucfirst($username), 'email' => $username.'@example.test', 'phone' => '012', 'username' => $username]);
        $affiliate->forceFill(['email_verified_at' => now(), 'profile_completed_at' => now()])->save();

        return $affiliate;
    }

    private function clickData(Affiliate $affiliate): array
    {
        return [
            'affiliate_id' => $affiliate->id, 'visitor_token' => (string) \Illuminate\Support\Str::uuid(), 'source' => 'direct',
            'device_type' => 'desktop', 'landing_path' => '/', 'clicked_at' => now(),
        ];
    }
}
