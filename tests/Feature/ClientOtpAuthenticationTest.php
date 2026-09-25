<?php

namespace Tests\Feature;

use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Identity\Services\EmailOtpService;
use App\Engines\Identity\Services\OtpRateLimited;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientOtpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_log_in_once_with_a_valid_otp(): void
    {
        $user = User::factory()->create(['email' => 'client@example.test', 'email_verified_at' => null]);
        $this->challenge($user, '123456');

        $this->post(route('client.otp.verify'), ['email' => $user->email, 'code' => '123456'])
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user, 'client');
        $this->assertDatabaseMissing('email_otp_challenges', ['email' => $user->email, 'consumed_at' => null]);
        $this->post(route('client.logout'));
        $this->post(route('client.otp.verify'), ['email' => $user->email, 'code' => '123456'])->assertSessionHasErrors('code');
    }

    public function test_three_failed_attempts_invalidate_the_otp(): void
    {
        $user = User::factory()->create(['email' => 'attempts@example.test']);
        $challenge = $this->challenge($user, '123456');

        foreach (range(1, 3) as $attempt) {
            $this->from(route('client.login'))->post(route('client.otp.verify'), ['email' => $user->email, 'code' => '000000'])->assertSessionHasErrors('code');
        }

        $this->assertDatabaseHas('email_otp_challenges', ['id' => $challenge->id, 'attempts' => 3]);
        $this->assertDatabaseMissing('email_otp_challenges', ['id' => $challenge->id, 'invalidated_at' => null]);
    }

    public function test_otp_requests_are_limited_to_five_per_hour(): void
    {
        Mail::fake();
        config(['identity.otp.resend_cooldown_seconds' => 0]);
        $service = app(EmailOtpService::class);

        foreach (range(1, 5) as $request) {
            $service->issue('rate@example.test', '127.0.0.1');
        }

        $this->expectException(OtpRateLimited::class);
        $service->issue('rate@example.test', '127.0.0.1');
    }

    public function test_a_new_otp_invalidates_the_previous_otp(): void
    {
        Mail::fake();
        config(['identity.otp.resend_cooldown_seconds' => 0]);
        $old = $this->challenge(User::factory()->create(['email' => 'replace@example.test']), '123456');

        app(EmailOtpService::class)->issue('replace@example.test', '127.0.0.1');

        $this->assertDatabaseMissing('email_otp_challenges', ['id' => $old->id, 'invalidated_at' => null]);
        $this->assertDatabaseCount('email_otp_challenges', 2);
    }

    public function test_an_expired_otp_cannot_authenticate_a_customer(): void
    {
        $user = User::factory()->create(['email' => 'expired@example.test']);
        $challenge = $this->challenge($user, '123456');
        $challenge->update(['expires_at' => now()->subMinute()]);

        $this->post(route('client.otp.verify'), ['email' => $user->email, 'code' => '123456'])->assertSessionHasErrors('code');

        $this->assertGuest('client');
        $this->assertDatabaseMissing('email_otp_challenges', ['id' => $challenge->id, 'invalidated_at' => null]);
    }

    public function test_login_code_is_only_sent_to_registered_emails_with_the_same_response(): void
    {
        Mail::fake();

        $unknown = $this->from(route('client.login'))->post(route('client.otp.send'), ['email' => 'unknown@example.test']);
        $unknown->assertRedirect(route('client.login'))->assertSessionHas('status', 'Kod log masuk telah dihantar ke email anda.');
        $this->assertDatabaseCount('email_otp_challenges', 0);

        User::factory()->create(['email' => 'known@example.test']);
        $known = $this->from(route('client.login'))->post(route('client.otp.send'), ['email' => 'Known@Example.test']);
        $known->assertRedirect(route('client.login'))->assertSessionHas('status', 'Kod log masuk telah dihantar ke email anda.');
        $this->assertDatabaseHas('email_otp_challenges', ['email' => 'known@example.test']);
    }

    private function challenge(User $user, string $code): EmailOtpChallenge
    {
        return EmailOtpChallenge::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailOtpChallenge::PURPOSE_CLIENT_LOGIN,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);
    }
}
