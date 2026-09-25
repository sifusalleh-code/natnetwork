<?php

namespace Tests\Feature;

use App\Engines\Communication\Models\CommunicationSetting;
use App\Engines\Communication\Models\EmailLog;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailDeliveryAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_mailer_is_not_reported_as_sent_and_user_sees_clear_error(): void
    {
        config(['mail.default' => 'log']);
        User::factory()->create(['email' => 'a@example.test']);

        $this->from(route('client.login'))->post(route('client.otp.send'), ['email' => 'a@example.test'])->assertSessionHasErrors('email');
        $this->assertSame('FAILED', EmailLog::query()->value('status'));
        $this->assertNotNull(EmailOtpChallenge::query()->value('invalidated_at'));
    }

    public function test_resend_failure_shows_message_not_500_and_retry_is_not_blocked_by_cooldown(): void
    {
        Http::fake(['api.resend.com/*' => Http::sequence()->push(['message' => 'The natnetwork.net domain is not verified.'], 403)->push(['id' => 'email_123'], 200)]);
        CommunicationSetting::current()->forceFill(['email_enabled' => true, 'resend_api_key' => 're_test', 'from_email' => 'noreply@natnetwork.net'])->save();
        User::factory()->create(['email' => 'b@example.test']);

        $this->from(route('client.login'))->post(route('client.otp.send'), ['email' => 'b@example.test'])->assertStatus(302)->assertSessionHasErrors('email');
        $this->from(route('client.login'))->post(route('client.otp.send'), ['email' => 'b@example.test'])->assertSessionHasNoErrors();
        $this->assertSame(['FAILED', 'SENT'], EmailLog::query()->orderBy('id')->pluck('status')->all());
    }

    public function test_contact_form_uses_resend_with_reply_to(): void
    {
        Http::fake(['api.resend.com/*' => Http::response(['id' => 'email_1'], 200)]);
        CommunicationSetting::current()->forceFill(['email_enabled' => true, 'resend_api_key' => 're_test', 'from_email' => 'noreply@natnetwork.net'])->save();
        config(['mail.default' => 'log']);

        $this->post('/contact', ['name' => 'Ali', 'email' => 'ali@example.test', 'subject' => 'Soalan', 'message' => 'Hai'])->assertRedirect(route('contact'))->assertSessionHas('status');
        Http::assertSent(fn ($request) => $request['reply_to'] === 'ali@example.test' && $request['to'] === [config('company.email')]);
    }
}
