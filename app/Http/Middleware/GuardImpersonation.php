<?php

namespace App\Http\Middleware;

use App\Engines\Audit\Services\AuditLogger;
use App\Http\Controllers\Admin\ImpersonationController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mod admin (log masuk sebagai ahli/pelanggan/affiliate/partner) — Keputusan Owner 25 Sep 2026 #5:
 * READONLY SAHAJA. Admin boleh melihat portal pengguna melalui mata pengguna, tetapi TIDAK boleh
 * membuat sebarang tindakan bagi pihak pengguna (bayaran, kelulusan, kemas kini profil, dll).
 * - sesi tamat jika admin tidak lagi log masuk;
 * - setiap permintaan bukan-GET/HEAD disekat sepenuhnya (kecuali log keluar impersonation itu sendiri);
 * - percubaan tindakan disekat direkod dalam audit log.
 */
class GuardImpersonation
{
    public const KEY = 'impersonation';

    public const LOGOUT_ROUTES = ['client.logout', 'affiliate.logout', 'partner.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->hasSession() ? $request->session()->get(self::KEY) : null;
        if (! $session) {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin || (int) $admin->id !== (int) $session['admin_id']) {
            self::end($request);

            return redirect()->route('admin.login');
        }

        $route = $request->route()?->getName();
        if (in_array($route, self::LOGOUT_ROUTES, true)) {
            return redirect()->route('admin.impersonate.stop.get');
        }

        // Tindakan admin sendiri (contoh: admin.impersonate.stop/start di tab lain) bukan tindakan "bagi pihak pengguna".
        if ($route && str_starts_with($route, 'admin.')) {
            return $next($request);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            app(AuditLogger::class)->record('IMPERSONATED_ACTION_BLOCKED', $admin, null, null, [
                'as' => $session['type'].'#'.$session['id'], 'route' => $route, 'method' => $request->method(),
            ]);

            return back()->withErrors(['impersonation' => 'Mod Admin: anda sedang melihat portal ini sebagai READONLY sahaja. Tindakan ini hanya boleh dibuat oleh pengguna sendiri.']);
        }

        return $next($request);
    }

    public static function end(Request $request): void
    {
        $session = $request->session()->get(self::KEY);
        if ($session && isset(ImpersonationController::TYPES[$session['type']])) {
            Auth::guard(ImpersonationController::TYPES[$session['type']][1])->logout();
        }
        $request->session()->forget(self::KEY);
    }
}
