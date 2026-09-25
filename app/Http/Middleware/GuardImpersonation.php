<?php

namespace App\Http\Middleware;

use App\Engines\Audit\Services\AuditLogger;
use App\Http\Controllers\Admin\ImpersonationController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mod admin (log masuk sebagai pengguna):
 * - sesi tamat jika admin tidak lagi log masuk;
 * - tindakan kewangan / kontraktual disekat (bayar, terima quotation, pegang slot, keputusan CR, tambah modal);
 * - setiap permintaan bukan-GET direkod dalam audit log sebagai tindakan admin bagi pihak pengguna.
 */
class GuardImpersonation
{
    public const KEY = 'impersonation';

    public const BLOCKED_ROUTES = [
        'client.billing.pay', 'client.quotations.accept', 'client.quotations.slot.hold', 'client.changes.decide',
        'partner.capital.store', 'partner.pay', 'affiliate.withdrawals.store',
    ];

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
        if (in_array($route, self::BLOCKED_ROUTES, true)) {
            return back()->withErrors(['impersonation' => 'Mod Admin: tindakan ini (bayaran / kelulusan) hanya boleh dibuat oleh pengguna sendiri.']);
        }

        $response = $next($request);

        if (! $request->isMethod('GET') && $route && ! str_starts_with($route, 'admin.')) {
            app(AuditLogger::class)->record('IMPERSONATED_ACTION', $admin, null, null, [
                'as' => $session['type'].'#'.$session['id'], 'route' => $route, 'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
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
