<?php

namespace Tests\Feature;

use App\Engines\Identity\Models\Admin;
use App\Engines\Identity\Services\TotpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_requires_2fa_enrolment_before_access(): void
    {
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);

        $this->post(route('admin.login.attempt'), ['email' => 'admin@example.test', 'password' => 'password-yang-panjang'])
            ->assertRedirect(route('admin.two-factor.setup'));
        $this->assertGuest('admin');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $secret = session('admin_2fa_setup_secret');
        $this->get(route('admin.two-factor.setup'))->assertOk()->assertSee(substr($secret, 0, 4));

        $this->post(route('admin.two-factor.setup.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $code = app(TotpService::class)->codeAt($secret, app(TotpService::class)->currentStep());
        $this->post(route('admin.two-factor.setup.confirm'), ['code' => $code])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('audit_logs', ['action' => 'ADMIN_2FA_ENABLED', 'entity_id' => $admin->id]);
        $this->get(route('admin.dashboard'))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_enrolled_admin_needs_valid_unused_totp(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        $this->post(route('admin.login.attempt'), ['email' => 'admin@example.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post(route('admin.login.attempt'), ['email' => 'admin@example.test', 'password' => 'password-yang-panjang'])
            ->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest('admin');

        $this->post(route('admin.two-factor.verify'), ['code' => '123456'])->assertSessionHasErrors('code');
        $code = $totp->codeAt($secret, $totp->currentStep());
        $this->post(route('admin.two-factor.verify'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        // Kod yang sama tidak boleh diguna semula
        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();
        $this->post(route('admin.login.attempt'), ['email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $this->post(route('admin.two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_client_and_affiliate_sessions_cannot_access_admin(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client, 'client')->get(route('admin.billing.settings'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.billing.invoices'))->assertRedirect(route('admin.login'));
    }

    public function test_totp_matches_rfc6238_vector(): void
    {
        $totp = app(TotpService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $totp->codeAt($secret, 1));
        $this->assertSame('081804', $totp->codeAt($secret, intdiv(1111111109, 30)));
        $this->assertSame(intdiv(1111111109, 30), $totp->matchStep($secret, '081804', 1111111109));
    }
}
