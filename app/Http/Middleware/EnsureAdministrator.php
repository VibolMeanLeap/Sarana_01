<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $roleName = strtolower((string) $request->user()?->role?->role_name);

        if ($roleName !== 'admin' || $request->user()?->status !== 'active') {
            abort(403, 'Administrator access is required to open this area.');
        }

        return $next($request);
    }
}
