<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Identity\Models\Admin;
use App\Engines\Identity\Services\TotpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const PENDING_KEY = 'admin_2fa_pending';
    private const SETUP_SECRET_KEY = 'admin_2fa_setup_secret';
    private const PENDING_TTL_SECONDS = 600;

    public function showLogin(): View|RedirectResponse
    {
        return Auth::guard('admin')->check() ? redirect()->route('admin.dashboard') : view('admin.auth.login');
    }

    public function login(Request $request, TotpService $totp): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email:filter'], 'password' => ['required', 'string']]);
        $admin = Admin::query()->where('email', mb_strtolower(trim($data['email'])))->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            throw ValidationException::withMessages(['email' => ['Emel atau password tidak sah.']]);
        }

        $request->session()->put(self::PENDING_KEY, ['id' => $admin->id, 'at' => time()]);

        if ($admin->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.challenge');
        }

        $request->session()->put(self::SETUP_SECRET_KEY, $totp->generateSecret());

        return redirect()->route('admin.two-factor.setup');
    }

    public function showSetup(Request $request, TotpService $totp): View|RedirectResponse
    {
        $admin = $this->pendingAdmin($request);
        $secret = $request->session()->get(self::SETUP_SECRET_KEY);
        if (! $admin || $admin->hasTwoFactorEnabled() || ! $secret) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor-setup', ['secret' => $secret, 'uri' => $totp->otpauthUri($secret, $admin->email)]);
    }

    public function confirmSetup(Request $request, TotpService $totp, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $admin = $this->pendingAdmin($request);
        $secret = $request->session()->get(self::SETUP_SECRET_KEY);
        if (! $admin || $admin->hasTwoFactorEnabled() || ! $secret) {
            return redirect()->route('admin.login');
        }

        if (! $totp->verifyForAdmin($admin, $data['code'], $secret)) {
            throw ValidationException::withMessages(['code' => ['Kod tidak sah. Pastikan masa telefon anda tepat dan cuba lagi.']]);
        }

        $admin->refresh()->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();
        $audit->record('ADMIN_2FA_ENABLED', $admin, $admin);

        return $this->completeLogin($request, $admin, $audit);
    }

    public function showChallenge(Request $request): View|RedirectResponse
    {
        $admin = $this->pendingAdmin($request);

        return $admin && $admin->hasTwoFactorEnabled() ? view('admin.auth.two-factor-challenge') : redirect()->route('admin.login');
    }

    public function challenge(Request $request, TotpService $totp, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $admin = $this->pendingAdmin($request);
        if (! $admin || ! $admin->hasTwoFactorEnabled()) {
            return redirect()->route('admin.login');
        }

        if (! $totp->verifyForAdmin($admin, $data['code'])) {
            throw ValidationException::withMessages(['code' => ['Kod 2FA tidak sah atau telah digunakan.']]);
        }

        return $this->completeLogin($request, $admin->refresh(), $audit);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function completeLogin(Request $request, Admin $admin, AuditLogger $audit): RedirectResponse
    {
        $request->session()->forget([self::PENDING_KEY, self::SETUP_SECRET_KEY]);
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();
        $audit->record('ADMIN_LOGIN', $admin, $admin);

        return redirect()->intended(route('admin.dashboard'));
    }

    private function pendingAdmin(Request $request): ?Admin
    {
        $pending = $request->session()->get(self::PENDING_KEY);
        if (! is_array($pending) || ! isset($pending['id'], $pending['at']) || time() - (int) $pending['at'] > self::PENDING_TTL_SECONDS) {
            $request->session()->forget([self::PENDING_KEY, self::SETUP_SECRET_KEY]);

            return null;
        }

        return Admin::query()->find($pending['id']);
    }
}
