<?php

namespace App\Http\Controllers;

use App\Engines\Identity\Services\EmailOtpService;
use App\Engines\Identity\Services\OtpRateLimited;
use App\Engines\Identity\Services\OtpVerificationFailed;
use App\Engines\Sales\Services\BuilderSessionService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientAuthController extends Controller
{
    public function create(): View
    {
        return view('client.login');
    }

    public function sendCode(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email:filter', 'max:255']]);

        try {
            if (User::query()->where('email', mb_strtolower(trim($data['email'])))->exists()) {
                $otp->issue($data['email'], (string) $request->ip());
            }
        } catch (OtpRateLimited $exception) {
            // Kod sebelumnya masih sah: kekalkan ruang kod supaya pengguna boleh terus memasukkannya.
            return back()->withInput()->withErrors(['email' => "Sila tunggu {$exception->retryAfterSeconds} saat sebelum meminta kod baharu."])->with('otp_email', $data['email']);
        }

        return back()->with('otp_email', $data['email'])->with('status', 'Kod log masuk telah dihantar ke email anda.');
    }

    public function verify(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);

        try {
            $user = $otp->verify($data['email'], $data['code']);
        } catch (OtpVerificationFailed $exception) {
            throw ValidationException::withMessages(['code' => [$exception->getMessage()]]);
        }

        // Emel disahkan: log masuk diingati 30 hari pada peranti ini.
        Auth::guard('client')->setRememberDuration(BuilderSessionService::RESUME_DAYS * 24 * 60);
        Auth::guard('client')->login($user, true);
        $request->session()->regenerate();

        // Start Project tersimpan (dalam 30 hari, belum di-reset) dibuka semula.
        $builderSessionId = app(BuilderSessionService::class)->latestFor($user)?->id;
        if ($builderSessionId) {
            $request->session()->put(BuilderSessionService::SESSION_KEY, $builderSessionId);
        }

        return redirect()->intended(route('client.dashboard'))->with('status', 'Anda telah berjaya log masuk.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
