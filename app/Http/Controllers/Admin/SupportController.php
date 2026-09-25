<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Communication\Models\SupportTicket;
use App\Engines\Communication\Services\SupportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'OPEN');

        return view('admin.support.index', [
            'tickets' => SupportTicket::query()->with(['customer', 'project'])->when($status !== 'ALL', fn ($q) => $q->where('status', $status))->latest('last_activity_at')->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        return view('admin.support.show', ['ticket' => $ticket->load(['customer', 'project', 'messages'])]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportService $support): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'close' => ['nullable', 'boolean']]);
        $support->adminReply(Auth::guard('admin')->user(), $ticket, $data['body'], (bool) ($data['close'] ?? false));

        return back()->with('status', 'Balasan dihantar.');
    }
}
