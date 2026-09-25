<?php

namespace App\Http\Middleware;

use App\Engines\Analytics\Services\WebTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Rekod paparan halaman awam (Analytics Engine) selepas respons 200 HTML dijana. */
class TrackWebPageView
{
    public function __construct(private readonly WebTracker $tracker)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $track = $this->tracker->shouldTrack($request);
        $publicId = $track ? (string) Str::uuid() : null;
        if ($publicId) {
            View::share('nnPageView', $publicId);
        }

        $response = $next($request);

        if ($publicId && $response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $this->tracker->record($request, $publicId);
        }

        return $response;
    }
}
