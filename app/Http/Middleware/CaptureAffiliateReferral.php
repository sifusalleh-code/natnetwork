<?php

namespace App\Http\Middleware;

use App\Engines\Affiliate\Services\AffiliateTrackingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAffiliateReferral
{
    public function __construct(private readonly AffiliateTrackingService $tracking)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if ($request->isMethod('GET') && is_string($ref) && preg_match('/^[A-Za-z]{3,12}$/', $ref)) {
            $this->tracking->recordClick($request, $ref);
        }

        return $next($request);
    }
}
