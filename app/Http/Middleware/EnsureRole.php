<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Restrict a route to one or more role names.
     * Example: ->middleware('role:admin,cashier')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $currentRole = strtolower((string) $request->user()?->role?->role_name);
        $allowedRoles = array_map('strtolower', $roles);

        abort_unless(
            $request->user()?->status === 'active'
                && $currentRole !== ''
                && in_array($currentRole, $allowedRoles, true),
            403,
            'You do not have permission to access this area.'
        );

        return $next($request);
    }
}
