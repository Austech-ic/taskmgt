<?php

use App\Http\Middleware\EnsureActive;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\RequireOrganization;
use App\Http\Middleware\RequireStaff;
use App\Http\Middleware\RequireWorkspace;
use App\Http\Middleware\VerifiedIfRequired;
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
        $middleware->web(prepend: [EnsureInstalled::class]);
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'active' => EnsureActive::class,
            'verified.if' => VerifiedIfRequired::class,
            'workspace' => RequireWorkspace::class,
            'has.org' => RequireOrganization::class,
            'staff' => RequireStaff::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('app.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
