<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\CaptureAffiliateReferral::class, \App\Http\Middleware\GuardImpersonation::class, \App\Http\Middleware\TrackWebPageView::class]);
        $middleware->alias([
            'affiliate.access' => \App\Http\Middleware\EnsureAffiliateAccess::class,
            'admin.auth' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            'client.portal' => \App\Http\Middleware\EnsureClientAuthenticated::class,
            'partner.access' => \App\Http\Middleware\EnsurePartnerAuthenticated::class,
        ]);
        $middleware->validateCsrfTokens(except: ['billing/billplz/callback']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
