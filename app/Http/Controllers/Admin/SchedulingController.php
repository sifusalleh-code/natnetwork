<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Scheduling\Models\SchedulingSetting;
use App\Engines\Scheduling\Models\SlotHold;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SchedulingController extends Controller
{
    public function queue(SchedulingService $scheduling): View
    {
        $scheduling->expireStale();
        $settings = SchedulingSetting::current();
        $holds = SlotHold::query()->occupying()->with('quotation.request.customer')->orderBy('start_date')->get();
        $first = CarbonImmutable::now()->startOfWeek();
        $weeks = collect(range(0, $settings->weeks_ahead))->map(fn ($i) => [
            'start' => $w = $first->addWeeks($i),
            'holds' => $holds->filter(fn (SlotHold $h) => $h->start_date->lessThanOrEqualTo($w) && $h->endDate()->greaterThan($w)),
        ]);

        return view('admin.projects.queue', ['settings' => $settings, 'weeks' => $weeks, 'holds' => $holds, 'conflicts' => $holds->where('conflict', true)]);
    }

    public function updateSettings(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'max_active_projects' => ['required', 'integer', 'min:1', 'max:50'],
            'hold_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'weeks_ahead' => ['required', 'integer', 'min:2', 'max:52'],
        ]);
        $settings = SchedulingSetting::current();
        $previous = $settings->only(array_keys($data));
        $settings->update($data);
        $audit->record('SCHEDULING_SETTINGS_CHANGED', Auth::guard('admin')->user(), $settings, $previous, $data);

        return back()->with('status', 'Tetapan jadual disimpan.');
    }
}
