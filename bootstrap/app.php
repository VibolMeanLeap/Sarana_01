<?php

use App\Http\Middleware\EnsureAdministrator;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'administrator' => EnsureAdministrator::class,
            'role' => EnsureRole::class,
        ]);

        // ABA calls this endpoint server-to-server without a browser CSRF token.
        // The callback still triggers an authoritative server-to-server status check.
        $middleware->validateCsrfTokens(except: [
            'web/payments/payway/callback',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('web/*') ? route('webLogin') : route('showlogin'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep Laravel's standard exception handling for the demo environment.
    })->create();
