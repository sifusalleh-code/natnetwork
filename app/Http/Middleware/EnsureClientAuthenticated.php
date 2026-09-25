<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Portal pelanggan: belum log masuk → halaman login pelanggan (bukan route 'login' yang tidak wujud). */
class EnsureClientAuthenticated
{
    public const PRE_ORDER_ROUTES = ['client.quotations', 'client.quotations.*', 'client.billing', 'client.billing.*', 'client.profile', 'client.profile.*', 'client.notifications', 'client.notifications.*', 'builder.reset', 'builder.new'];

    public static function portalReady(int $userId): bool
    {
        return \App\Engines\Sales\Models\Order::query()->where('customer_user_id', $userId)->exists();
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('client')->check()) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('client.login');
        }

        // Portal penuh disediakan selepas bayaran pertama berjaya (Order disahkan). Sebelum itu hanya langkah
        // Start Project (quotation, slot, invois & bayaran), profil dan notifikasi dibenarkan.
        if (! self::portalReady((int) Auth::guard('client')->id()) && ! $request->routeIs(self::PRE_ORDER_ROUTES)) {
            return redirect()->route('builder.start')->with('builder_status', 'Portal Client dibuka selepas bayaran pertama berjaya. Teruskan Start Project anda di bawah.');
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
