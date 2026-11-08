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

        // ABA sends server-to-server callbacks without a browser CSRF token.
        // Keep the old endpoint temporarily for QR codes issued before the /store migration.
        $middleware->validateCsrfTokens(except: [
            'store/payments/payway/callback',
            'web/payments/payway/callback',
            'telegram/webhook',
        ]);

        // Website URLs now live under /store. JSON polling requests should receive
        // 401 JSON responses (Accept: application/json), not an HTML login redirect.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('store', 'store/*', 'web', 'web/*')) {
                return route('webLogin');
            }

            return route('showlogin');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep Laravel's standard exception handling for the demo environment.
    })->create();
