<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Identity\Services\EmailOtpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AffiliateOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_otp_and_creates_affiliate_only_after_verification(): void
    {
        Mail::fake();

        $this->post(route('affiliate.register.send'), ['name' => 'Siti Aminah', 'email' => 'Siti@Example.test', 'phone' => '0123456789'])
            ->assertRedirect(route('affiliate.register'));

        $this->assertDatabaseCount('affiliates', 0);
        $this->assertDatabaseHas('email_otp_challenges', ['email' => 'siti@example.test', 'purpose' => EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN, 'user_id' => null]);

        $this->replaceChallenge('siti@example.test', '123456');
        $this->post(route('affiliate.register.verify'), ['code' => '123456'])->assertRedirect(route('affiliate.profile'));

        $affiliate = Affiliate::query()->where('email', 'siti@example.test')->firstOrFail();
        $this->assertSame('Siti Aminah', $affiliate->name);
        $this->assertSame('0123456789', $affiliate->phone);
        $this->assertNotNull($affiliate->email_verified_at);
        $this->assertAuthenticatedAs($affiliate, 'affiliate');
        $this->assertGuest('client');
    }

    public function test_existing_affiliate_email_goes_straight_to_login(): void
    {
        Mail::fake();
        Affiliate::query()->create(['name' => 'Ali', 'email' => 'ali@example.test', 'phone' => '011']);

        $this->post(route('affiliate.register.send'), ['name' => 'Ali Lain', 'email' => 'ALI@example.test', 'phone' => '012'])
            ->assertRedirect(route('affiliate.login'))
            ->assertSessionHas('login_email', 'ali@example.test');

        $this->assertDatabaseCount('email_otp_challenges', 0);
        $this->assertSame('Ali', Affiliate::query()->where('email', 'ali@example.test')->value('name'));
    }

    public function test_client_email_can_register_as_separate_affiliate(): void
    {
        Mail::fake();
        $client = User::factory()->create(['email' => 'client@example.test']);

        $this->post(route('affiliate.register.send'), ['name' => 'Client', 'email' => 'client@example.test', 'phone' => '012'])
            ->assertRedirect(route('affiliate.register'));
        $this->replaceChallenge('client@example.test', '654321');
        $this->post(route('affiliate.register.verify'), ['code' => '654321'])->assertRedirect(route('affiliate.profile'));

        $this->assertDatabaseHas('affiliates', ['email' => 'client@example.test']);
        $this->assertDatabaseHas('users', ['id' => $client->id, 'email' => 'client@example.test']);
    }

    public function test_affiliate_otp_cannot_log_in_a_client_and_client_otp_cannot_log_in_an_affiliate(): void
    {
        $user = User::factory()->create(['email' => 'both@example.test']);
        $affiliate = Affiliate::query()->create(['name' => 'Both', 'email' => 'both@example.test', 'phone' => '012']);

        $this->replaceChallenge('both@example.test', '111111', EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN);
        $this->post(route('client.otp.verify'), ['email' => 'both@example.test', 'code' => '111111'])->assertSessionHasErrors('code');
        $this->assertGuest('client');

        $this->replaceChallenge('both@example.test', '222222', EmailOtpChallenge::PURPOSE_CLIENT_LOGIN);
        $this->post(route('affiliate.otp.verify'), ['email' => 'both@example.test', 'code' => '222222'])->assertSessionHasErrors('code');
        $this->assertGuest('affiliate');

        $this->replaceChallenge('both@example.test', '333333', EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN);
        $this->post(route('affiliate.otp.verify'), ['email' => 'both@example.test', 'code' => '333333'])->assertRedirect(route('affiliate.dashboard'));
        $this->assertAuthenticatedAs($affiliate, 'affiliate');
        $this->assertGuest('client');
        $this->assertNotNull($user->fresh());
    }

    public function test_login_code_response_is_the_same_for_unknown_emails(): void
    {
        Mail::fake();
        Affiliate::query()->create(['name' => 'Known', 'email' => 'known@example.test', 'phone' => '012']);

        $this->post(route('affiliate.otp.send'), ['email' => 'unknown@example.test'])->assertSessionHas('status', 'Kod log masuk telah dihantar ke emel anda.');
        $this->post(route('affiliate.otp.send'), ['email' => 'known@example.test'])->assertSessionHas('status', 'Kod log masuk telah dihantar ke emel anda.');

        $this->assertSame(1, EmailOtpChallenge::query()->where('purpose', EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN)->count());
        $this->assertSame(0, EmailOtpChallenge::query()->where('email', 'unknown@example.test')->count());
    }

    public function test_incomplete_profile_is_redirected_to_profile_on_every_portal_route(): void
    {
        $affiliate = $this->affiliate();

        foreach (['affiliate.dashboard', 'affiliate.studio-poster', 'affiliate.wallet'] as $route) {
            $this->actingAs($affiliate, 'affiliate')->get(route($route))->assertRedirect(route('affiliate.profile'));
        }

        $this->actingAs($affiliate, 'affiliate')->get(route('affiliate.profile'))
            ->assertOk()
            ->assertDontSee(route('affiliate.wallet'))
            ->assertSee('wajib atas nama pemilik akaun affiliate');
    }

    public function test_guests_are_sent_to_affiliate_login(): void
    {
        foreach (['affiliate.dashboard', 'affiliate.studio-poster', 'affiliate.wallet', 'affiliate.profile'] as $route) {
            $this->get(route($route))->assertRedirect(route('affiliate.login'));
        }
    }

    public function test_completing_profile_opens_the_portal_and_locks_username(): void
    {
        Storage::fake('local');
        $affiliate = $this->affiliate();

        $this->actingAs($affiliate, 'affiliate')->post(route('affiliate.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('me.jpg', 400, 400)])
            ->assertRedirect(route('affiliate.profile'));
        $this->actingAs($affiliate, 'affiliate')->put(route('affiliate.profile.update'), $this->profilePayload())
            ->assertRedirect(route('affiliate.profile'));

        $affiliate->refresh();
        $this->assertTrue($affiliate->isProfileComplete());
        $this->assertSame('siti', $affiliate->username);
        $this->assertSame('3456', $affiliate->bank_account_last4);
        $this->assertSame('1234563456', $affiliate->bank_account_number);
        $this->assertNotSame('1234563456', \DB::table('affiliates')->where('id', $affiliate->id)->value('bank_account_number'));
        Storage::disk('local')->assertExists($affiliate->avatar_path);

        foreach (['affiliate.dashboard', 'affiliate.studio-poster', 'affiliate.wallet'] as $route) {
            $this->actingAs($affiliate, 'affiliate')->get(route($route))->assertOk();
        }

        $this->actingAs($affiliate, 'affiliate')->put(route('affiliate.profile.update'), $this->profilePayload(['username' => 'lain']))
            ->assertSessionHasErrors('username');
        $this->assertSame('siti', $affiliate->fresh()->username);

        $this->actingAs($affiliate, 'affiliate')->put(route('affiliate.profile.update'), $this->profilePayload(['username' => null, 'bank_account_number' => '']))
            ->assertSessionHasNoErrors();
        $this->assertSame('1234563456', $affiliate->fresh()->bank_account_number);
    }

    public function test_profile_without_avatar_stays_incomplete_and_invalid_input_is_rejected(): void
    {
        $affiliate = $this->affiliate();

        $this->actingAs($affiliate, 'affiliate')->put(route('affiliate.profile.update'), $this->profilePayload())->assertSessionHasNoErrors();
        $this->assertFalse($affiliate->fresh()->isProfileComplete());

        $other = Affiliate::query()->create(['name' => 'Other', 'email' => 'other@example.test', 'phone' => '012']);
        $this->actingAs($other, 'affiliate')->put(route('affiliate.profile.update'), $this->profilePayload(['username' => 'siti']))->assertSessionHasErrors('username');
        $this->actingAs($other, 'affiliate')->put(route('affiliate.profile.update'), $this->profilePayload(['username' => 'Bad Name', 'state' => 'Mars', 'bank_name' => 'X', 'bank_account_number' => '12-34']))
            ->assertSessionHasErrors(['username', 'state', 'bank_name', 'bank_account_number']);
        $this->actingAs($other, 'affiliate')->post(route('affiliate.profile.avatar.update'), ['avatar' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('avatar');
    }

    public function test_avatar_is_only_served_to_its_owner(): void
    {
        Storage::fake('local');
        $owner = $this->affiliate();
        $this->actingAs($owner, 'affiliate')->post(route('affiliate.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('me.png')]);
        $this->actingAs($owner, 'affiliate')->get(route('affiliate.profile.avatar'))->assertOk();

        $this->post(route('affiliate.logout'));
        $this->app['auth']->forgetGuards();
        $this->get(route('affiliate.profile.avatar'))->assertRedirect(route('affiliate.login'));
    }

    public function test_client_otp_flow_is_unchanged_by_affiliate_purpose(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'client@example.test']);

        app(EmailOtpService::class)->issue('client@example.test', '127.0.0.1');

        $this->assertDatabaseHas('email_otp_challenges', ['email' => 'client@example.test', 'purpose' => EmailOtpChallenge::PURPOSE_CLIENT_LOGIN]);
        $this->assertNotNull(EmailOtpChallenge::query()->where('email', 'client@example.test')->value('user_id'));
    }

    private function affiliate(): Affiliate
    {
        $affiliate = Affiliate::query()->create(['name' => 'Siti Aminah', 'email' => 'siti@example.test', 'phone' => '0123456789']);
        $affiliate->forceFill(['email_verified_at' => now()])->save();

        return $affiliate;
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'siti',
            'phone' => '0123456789',
            'state' => 'Selangor',
            'bank_name' => 'Maybank',
            'bank_account_number' => '123456 3456',
        ], $overrides);
    }

    private function replaceChallenge(string $email, string $code, string $purpose = EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN): void
    {
        EmailOtpChallenge::query()->where('email', $email)->where('purpose', $purpose)->delete();
        EmailOtpChallenge::query()->create([
            'email' => $email, 'purpose' => $purpose, 'code_hash' => Hash::make($code),
            'attempts' => 0, 'sent_at' => now(), 'expires_at' => now()->addMinutes(10),
        ]);
    }
}
