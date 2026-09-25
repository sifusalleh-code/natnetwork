<?php

namespace App\Http\Controllers;

use App\Engines\Analytics\Services\WebTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Terima ping & klik CTA daripada halaman awam. Sentiasa 204 supaya tiada maklumat didedahkan. */
class WebAnalyticsController extends Controller
{
    public function ping(Request $request, WebTracker $tracker): Response
    {
        $tracker->ping($request, (string) $request->input('pv'));

        return response()->noContent();
    }

    public function event(Request $request, WebTracker $tracker): Response
    {
        $tracker->event($request, (string) $request->input('pv'), (string) $request->input('cta'));

        return response()->noContent();
    }
}
