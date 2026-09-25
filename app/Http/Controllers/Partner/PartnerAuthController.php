<?php

namespace App\Http\Controllers\Partner;

use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Identity\Services\EmailOtpService;
use App\Engines\Identity\Services\OtpRateLimited;
use App\Engines\Identity\Services\OtpVerificationFailed;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerSetting;
use App\Engines\Partnership\Services\PartnerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartnerAuthController extends Controller
{
    private const PENDING_KEY = 'partner_registration';
    public const BANKS = ['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank', 'AmBank', 'Bank Islam', 'Bank Rakyat', 'Bank Muamalat', 'Affin Bank', 'Alliance Bank', 'OCBC Bank', 'HSBC Bank', 'UOB Bank', 'Standard Chartered', 'Agrobank', 'BSN', 'MBSB Bank', 'Al Rajhi Bank'];

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::guard('partner')->check()) {
            return redirect()->route('partner.dashboard');
        }

        $pending = $request->session()->get(self::PENDING_KEY);

        return view('partner.register', [
            'settings' => PartnerSetting::current(),
            'open' => PartnerSetting::current()->program_enabled,
            'pending' => $pending ? decrypt($pending) : null,
        ]);
    }

    /** Langkah 3: maklumat asas + amaun modal. Terma mesti dipersetujui (disemak semula di pelayan). */
    public function register(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $settings = PartnerSetting::current();
        abort_unless($settings->program_enabled, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'id_type' => ['required', Rule::in(['IC', 'COMPANY'])],
            'company_name' => ['required_if:id_type,COMPANY', 'nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:'.(float) $settings->min_capital, 'max:'.(float) $settings->max_total_capital],
            'agree' => ['accepted'],
        ], [
            'agree.accepted' => 'Sila baca dan tandakan persetujuan terhadap terma dan syarat.',
            'amount.min' => 'Modal minimum ialah RM '.number_format((float) $settings->min_capital, 2).'.',
        ]);
        $data['email'] = mb_strtolower(trim($data['email']));

        if (Partner::query()->where('email', $data['email'])->exists()) {
            $request->session()->forget(self::PENDING_KEY);

            return redirect()->route('partner.login')->with('login_email', $data['email'])->with('status', 'Emel ini sudah berdaftar dalam Program Partnership. Sila log masuk.');
        }

        try {
            $otp->issue($data['email'], (string) $request->ip(), EmailOtpChallenge::PURPOSE_PARTNER_LOGIN);
        } catch (OtpRateLimited $e) {
            throw ValidationException::withMessages(['email' => ["Sila tunggu {$e->retryAfterSeconds} saat sebelum meminta kod baharu."]]);
        }

        unset($data['agree']);
        $data['terms_ip'] = (string) $request->ip();
        $data['terms_user_agent'] = (string) $request->userAgent();
        $request->session()->put(self::PENDING_KEY, encrypt($data));

        return redirect()->route('partner.register')->with('status', 'Kod pengesahan telah dihantar ke emel anda.');
    }

    /** Sahkan emel → akaun dicipta & log masuk → invois modal → langkah bayaran. */
    public function verifyRegistration(Request $request, EmailOtpService $otp, PartnerService $partners): RedirectResponse
    {
        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];
        $pending = $request->session()->get(self::PENDING_KEY);
        $data = $pending ? decrypt($pending) : null;
        if (! is_array($data)) {
            throw ValidationException::withMessages(['code' => ['Sila isi maklumat pendaftaran terlebih dahulu.']]);
        }

        try {
            $otp->consume($data['email'], $code, EmailOtpChallenge::PURPOSE_PARTNER_LOGIN);
        } catch (OtpVerificationFailed $e) {
            throw ValidationException::withMessages(['code' => [$e->getMessage()]]);
        }

        $partner = $partners->register($data);
        $request->session()->forget(self::PENDING_KEY);
        Auth::guard('partner')->login($partner);
        $request->session()->regenerate();

        try {
            $partners->contribute($partner, (string) $data['amount'], (string) $request->ip(), (string) $request->userAgent());
        } catch (ValidationException $e) {
            return redirect()->route('partner.onboarding')->withErrors($e->errors());
        }

        return redirect()->route('partner.onboarding')->with('status', 'Emel disahkan. Sila buat bayaran modal untuk mengaktifkan penyertaan anda.');
    }

    public function cancelRegistration(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PENDING_KEY);

        return redirect()->route('partner.register');
    }

    public function showLogin(): View|RedirectResponse
    {
        return Auth::guard('partner')->check() ? redirect()->route('partner.dashboard') : view('partner.login');
    }

    public function sendCode(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $email = mb_strtolower(trim($request->validate(['email' => ['required', 'string', 'email:filter', 'max:255']])['email']));
        try {
            if (Partner::query()->where('email', $email)->exists()) {
                $otp->issue($email, (string) $request->ip(), EmailOtpChallenge::PURPOSE_PARTNER_LOGIN);
            }
        } catch (OtpRateLimited $e) {
            // Kod sebelumnya masih sah: kekalkan ruang kod supaya pengguna boleh terus memasukkannya.
            return redirect()->route('partner.login')->withInput()->withErrors(['email' => "Sila tunggu {$e->retryAfterSeconds} saat sebelum meminta kod baharu."])->with('otp_email', $email);
        }

        return redirect()->route('partner.login')->with('otp_email', $email)->with('status', 'Jika emel berdaftar, kod log masuk telah dihantar.');
    }

    public function verify(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email:filter', 'max:255'], 'code' => ['required', 'digits:6']]);
        $email = mb_strtolower(trim($data['email']));
        $partner = Partner::query()->where('email', $email)->first();

        try {
            if (! $partner) {
                throw new OtpVerificationFailed('Kod tidak sah atau telah tamat tempoh.');
            }
            $otp->consume($email, $data['code'], EmailOtpChallenge::PURPOSE_PARTNER_LOGIN);
        } catch (OtpVerificationFailed $e) {
            throw ValidationException::withMessages(['code' => [$e->getMessage()]]);
        }

        Auth::guard('partner')->login($partner);
        $request->session()->regenerate();

        return redirect()->route($partner->onboardingStep() === 5 ? 'partner.dashboard' : 'partner.onboarding');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('partner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }
}
