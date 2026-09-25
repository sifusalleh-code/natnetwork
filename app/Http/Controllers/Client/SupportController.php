<?php

namespace App\Http\Controllers\Client;

use App\Engines\Communication\Models\SupportTicket;
use App\Engines\Communication\Services\SupportService;
use App\Engines\Project\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(): View
    {
        $userId = Auth::guard('client')->id();

        return view('client.portal.support', [
            'tickets' => SupportTicket::query()->with('project')->where('customer_user_id', $userId)->latest('last_activity_at')->get(),
            'projects' => Project::query()->where('customer_user_id', $userId)->where('status', '!=', Project::CANCELLED)->latest('id')->get(),
        ]);
    }

    public function store(Request $request, SupportService $support): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'project_id' => ['nullable', 'integer'],
        ]);
        $project = ! empty($data['project_id']) ? Project::query()->find($data['project_id']) : null;
        abort_if(! empty($data['project_id']) && ! $project, 404);
        $ticket = $support->open(Auth::guard('client')->user(), $data['subject'], $data['body'], $project);

        return redirect()->route('client.support.show', $ticket)->with('status', 'Tiket '.$ticket->number.' dibuka.');
    }

    public function show(SupportTicket $ticket): View
    {
        abort_unless((int) $ticket->customer_user_id === (int) Auth::guard('client')->id(), 404);

        return view('client.portal.support-ticket', ['ticket' => $ticket->load(['project', 'messages'])]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportService $support): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $support->clientReply(Auth::guard('client')->user(), $ticket, $data['body']);

        return back()->with('status', 'Balasan dihantar.');
    }
}
