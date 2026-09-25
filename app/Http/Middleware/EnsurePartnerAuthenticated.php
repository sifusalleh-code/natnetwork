<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerAuthenticated
{
    public function handle(Request $request, Closure $next, ?string $requirement = null): Response
    {
        $partner = Auth::guard('partner')->user();
        if (! $partner) {
            return redirect()->route('partner.login');
        }
        if ($partner->isSuspended()) {
            Auth::guard('partner')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();


            return redirect()->route('partner.login')->withErrors(['email' => 'Akaun ini telah digantung. Sila hubungi kami.']);
        }
        // Dashboard & menu portal hanya dibuka selepas bayaran modal berjaya dan profil lengkap.
        if ($requirement === 'onboarded' && $partner->onboardingStep() < 5) {
            return redirect()->route('partner.onboarding');
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
