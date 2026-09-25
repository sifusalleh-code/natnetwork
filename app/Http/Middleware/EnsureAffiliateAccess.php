<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sekatan portal affiliate pada setiap route:
 * - belum log masuk → halaman login affiliate
 * - `affiliate.access:profile` + profil belum lengkap → halaman profil
 */
class EnsureAffiliateAccess
{
    public function handle(Request $request, Closure $next, ?string $requirement = null): Response
    {
        $affiliate = Auth::guard('affiliate')->user();

        if (! $affiliate) {
            return redirect()->route('affiliate.login');
        }

        if ($affiliate->isSuspended()) {
            Auth::guard('affiliate')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();


            return redirect()->route('affiliate.login')->withErrors(['email' => 'Akaun ini telah digantung. Sila hubungi kami.']);
        }

        if ($requirement === 'profile' && ! $affiliate->isProfileComplete()) {
            return redirect()->route('affiliate.profile')->with('status', 'Sila lengkapkan profil anda untuk membuka Dashboard dan menu affiliate.');
        }

        return $next($request);
    }
}
