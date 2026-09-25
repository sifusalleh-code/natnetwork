<?php

namespace Tests\Feature;

use App\Adapters\Email\OtpMailSender;
use App\Engines\Affiliate\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OtpFlowResilienceTest extends TestCase
{
    use RefreshDatabase;

    private array $codes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $sender = Mockery::mock(OtpMailSender::class);
        $sender->shouldReceive('send')->andReturnUsing(function (string $email, string $code) { $this->codes[$email] = $code; });
        $this->app->instance(OtpMailSender::class, $sender);
    }

    public function test_client_login_keeps_code_field_after_wrong_code_and_during_cooldown(): void
    {
        User::factory()->create(['email' => 'ali@example.test']);

        $this->from('/client/login')->post(route('client.otp.send'), ['email' => 'ali@example.test'])->assertRedirect('/client/login');
        $this->from('/client/login')->post(route('client.otp.verify'), ['email' => 'ali@example.test', 'code' => $this->wrong('ali@example.test')])->assertSessionHasErrors('code');
        $this->get('/client/login')->assertSee('Kod enam digit')->assertSee('Kod tidak sah atau telah tamat tempoh.');

        $this->from('/client/login')->post(route('client.otp.send'), ['email' => 'ali@example.test'])->assertSessionHasErrors('email');
        $this->get('/client/login')->assertSee('Kod enam digit')->assertSee('Sila tunggu');

        $this->post(route('client.otp.verify'), ['email' => 'ali@example.test', 'code' => $this->codes['ali@example.test']])->assertRedirect(route('client.dashboard'));
    }

    public function test_used_code_does_not_block_login_on_another_device(): void
    {
        User::factory()->create(['email' => 'ali@example.test']);
        $this->post(route('client.otp.send'), ['email' => 'ali@example.test']);
        $this->post(route('client.otp.verify'), ['email' => 'ali@example.test', 'code' => $this->codes['ali@example.test']])->assertRedirect(route('client.dashboard'));

        auth('client')->logout();
        $this->flushSession();
        $this->post(route('client.otp.send'), ['email' => 'ali@example.test'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('email_otp_challenges', 2);
    }

    public function test_affiliate_login_keeps_code_field_during_cooldown(): void
    {
        $affiliate = Affiliate::query()->create(['name' => 'Siti', 'email' => 'siti@example.test', 'phone' => '012']);
        $affiliate->forceFill(['email_verified_at' => now()])->save();

        $this->post(route('affiliate.otp.send'), ['email' => 'siti@example.test']);
        $this->post(route('affiliate.otp.send'), ['email' => 'siti@example.test'])->assertSessionHasErrors('email');
        $this->get(route('affiliate.login'))->assertSee('Kod enam digit')->assertSee('Sila tunggu');
        $this->post(route('affiliate.otp.verify'), ['email' => 'siti@example.test', 'code' => $this->codes['siti@example.test']])->assertRedirect(route('affiliate.dashboard'));
    }

    public function test_builder_keeps_code_field_after_wrong_code_and_during_cooldown(): void
    {
        $this->seed();
        $this->get(route('builder.start'));
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED']);
        $contact = ['name' => 'Ali', 'company' => null, 'email' => 'ali@example.test', 'phone' => '012'];

        $this->post(route('builder.identity'), $contact)->assertSessionHasNoErrors();
        $this->post(route('builder.verify'), ['code' => $this->wrong('ali@example.test')])->assertSessionHasErrors('code');
        $this->get(route('builder.start'))->assertSee('Sahkan emel', false)->assertSee('name="code"', false)->assertSee('Kod tidak sah atau telah tamat tempoh.');

        $this->post(route('builder.identity'), $contact)->assertSessionHasErrors('email');
        $this->get(route('builder.start'))->assertSee('name="code"', false)->assertSee('Sila tunggu');

        $this->post(route('builder.verify'), ['code' => $this->codes['ali@example.test']])->assertSessionHasNoErrors();
        $this->assertAuthenticated('client');
    }

    private function wrong(string $email): string
    {
        return $this->codes[$email] === '000000' ? '111111' : '000000';
    }
}
