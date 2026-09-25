<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Partnership\Models\Partner;
use App\Http\Controllers\Controller;
use App\Http\Middleware\GuardImpersonation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Admin log masuk sebagai client / affiliate / partner. Semua tindakan direkod; bayaran & kelulusan disekat (GuardImpersonation). */
class ImpersonationController extends Controller
{
    public const TYPES = [
        'client' => [User::class, 'client', 'client.dashboard', 'admin.clients.index'],
        'affiliate' => [Affiliate::class, 'affiliate', 'affiliate.dashboard', 'admin.affiliates.index'],
        'partner' => [Partner::class, 'partner', 'partner.dashboard', 'admin.partners.show'],
    ];

    public function start(Request $request, string $type, int $id, AuditLogger $audit): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        [$model, $guard, $home] = self::TYPES[$type];
        $target = $model::query()->findOrFail($id);
        $admin = Auth::guard('admin')->user();

        GuardImpersonation::end($request);
        Auth::guard($guard)->login($target);
        $request->session()->put(GuardImpersonation::KEY, ['admin_id' => $admin->id, 'type' => $type, 'id' => $target->id, 'name' => $target->name, 'started_at' => now()->toIso8601String(), 'back' => url()->previous()]);
        $audit->record('IMPERSONATION_STARTED', $admin, $target, null, ['type' => $type]);

        return redirect()->route($home);
    }

    public function stop(Request $request, AuditLogger $audit): RedirectResponse
    {
        $session = $request->session()->get(GuardImpersonation::KEY);
        GuardImpersonation::end($request);
        if ($session) {
            $audit->record('IMPERSONATION_ENDED', Auth::guard('admin')->user(), null, null, ['type' => $session['type'], 'id' => $session['id']]);
        }
        $back = $session['back'] ?? null;

        return $back && str_starts_with($back, url('/admin')) ? redirect()->to($back) : redirect()->route('admin.dashboard');
    }
}
