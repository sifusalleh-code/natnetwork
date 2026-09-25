<?php

namespace App\Http\Controllers\Client;

use App\Engines\Communication\Models\PortalNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('client.portal.notifications', ['notifications' => PortalNotification::query()->for('CLIENT', Auth::guard('client')->id())->latest('id')->paginate(30)]);
    }

    public function open(PortalNotification $notification): RedirectResponse
    {
        abort_unless($notification->recipient_type === 'CLIENT' && (int) $notification->recipient_id === (int) Auth::guard('client')->id(), 404);
        $notification->read_at ??= now();
        $notification->save();

        return $notification->url && str_starts_with($notification->url, url('/'))
            ? redirect()->to($notification->url)
            : redirect()->route('client.notifications');
    }

    public function readAll(): RedirectResponse
    {
        PortalNotification::query()->for('CLIENT', Auth::guard('client')->id())->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'Semua notifikasi ditanda sebagai dibaca.');
    }
}
