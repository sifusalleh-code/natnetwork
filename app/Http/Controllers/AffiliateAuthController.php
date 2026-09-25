<?php

namespace App\Http\Controllers;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Identity\Services\EmailOtpService;
use App\Engines\Identity\Services\OtpRateLimited;
use App\Engines\Identity\Services\OtpVerificationFailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AffiliateAuthController extends Controller
{
    private const PENDING_KEY = 'affiliate_registration';

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::guard('affiliate')->check()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('affiliate.register', ['pending' => $request->session()->get(self::PENDING_KEY)]);
    }

    public function register(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ]);
        $email = mb_strtolower(trim($data['email']));

        // Semakan kewujudan akaun berdasarkan emel: akaun affiliate sedia ada terus ke halaman login.
        if (Affiliate::query()->where('email', $email)->exists()) {
            $request->session()->forget(self::PENDING_KEY);

            return redirect()->route('affiliate.login')
                ->with('login_email', $email)
                ->with('status', 'Emel ini sudah berdaftar sebagai affiliate. Sila log masuk.');
        }

        try {
            $otp->issue($email, (string) $request->ip(), EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN);
        } catch (OtpRateLimited $exception) {
            throw ValidationException::withMessages(['email' => ["Sila tunggu {$exception->retryAfterSeconds} saat sebelum meminta kod baharu."]]);
        }

        $request->session()->put(self::PENDING_KEY, ['name' => trim($data['name']), 'email' => $email, 'phone' => trim($data['phone'])]);

        return redirect()->route('affiliate.register')->with('status', 'Kod pengesahan telah dihantar ke emel anda.');
    }

    public function verifyRegistration(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! is_array($pending) || empty($pending['email'])) {
            throw ValidationException::withMessages(['code' => ['Sila isi maklumat pendaftaran terlebih dahulu.']]);
        }

        try {
            $otp->consume($pending['email'], $data['code'], EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN);
        } catch (OtpVerificationFailed $exception) {
            throw ValidationException::withMessages(['code' => [$exception->getMessage()]]);
        }

        $affiliate = DB::transaction(function () use ($pending): Affiliate {
            $affiliate = Affiliate::query()->firstOrCreate(['email' => $pending['email']], [
                'name' => $pending['name'],
                'phone' => $pending['phone'],
            ]);
            if (! $affiliate->email_verified_at) {
                $affiliate->forceFill(['email_verified_at' => now()])->save();
            }

            return $affiliate;
        });

        $request->session()->forget(self::PENDING_KEY);
        Auth::guard('affiliate')->login($affiliate);
        $request->session()->regenerate();

        return redirect()->route('affiliate.profile')->with('status', 'Emel anda telah disahkan. Sila lengkapkan profil untuk membuka Dashboard affiliate.');
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('affiliate')->check()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('affiliate.login');
    }

    public function sendCode(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email:filter', 'max:255']]);
        $email = mb_strtolower(trim($data['email']));

        try {
            if (Affiliate::query()->where('email', $email)->exists()) {
                $otp->issue($email, (string) $request->ip(), EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN);
            }
        } catch (OtpRateLimited $exception) {
            // Kod sebelumnya masih sah: kekalkan ruang kod supaya pengguna boleh terus memasukkannya.
            return redirect()->route('affiliate.login')->withInput()->withErrors(['email' => "Sila tunggu {$exception->retryAfterSeconds} saat sebelum meminta kod baharu."])->with('otp_email', $email);
        }

        return redirect()->route('affiliate.login')->with('otp_email', $email)->with('status', 'Kod log masuk telah dihantar ke emel anda.');
    }

    public function verify(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = mb_strtolower(trim($data['email']));
        $affiliate = Affiliate::query()->where('email', $email)->first();

        try {
            if (! $affiliate) {
                throw new OtpVerificationFailed('Kod tidak sah atau telah tamat tempoh.');
            }
            $otp->consume($email, $data['code'], EmailOtpChallenge::PURPOSE_AFFILIATE_LOGIN);
        } catch (OtpVerificationFailed $exception) {
            throw ValidationException::withMessages(['code' => [$exception->getMessage()]]);
        }

        if (! $affiliate->email_verified_at) {
            $affiliate->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('affiliate')->login($affiliate);
        $request->session()->regenerate();

        return redirect()->route('affiliate.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('affiliate')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login');
    }
}
