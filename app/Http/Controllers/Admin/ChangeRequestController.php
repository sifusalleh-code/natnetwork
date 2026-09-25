<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Services\ChangeRequestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'SUBMITTED');

        return view('admin.change-requests.index', [
            'changes' => ChangeRequest::query()->with(['customer', 'project'])->when($status !== 'ALL', fn ($q) => $q->where('status', $status))->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function assess(Request $request, ChangeRequest $changeRequest, ChangeRequestService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['IN_SCOPE', 'ADDITIONAL_WORK', 'DECLINE'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'extra_weeks' => ['nullable', 'integer', 'min:0', 'max:52'],
        ]);
        $service->assess(Auth::guard('admin')->user(), $changeRequest, $data['decision'], $data['admin_note'] ?? null, isset($data['amount']) ? (string) $data['amount'] : null, (int) ($data['extra_weeks'] ?? 0));

        return back()->with('status', 'Penilaian '.$changeRequest->number.' disimpan dan pelanggan dimaklumkan.');
    }
}
