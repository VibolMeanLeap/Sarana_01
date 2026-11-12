<?php

namespace App\Http\Middleware;

use App\Services\LoginAttemptSecurity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProtectLoginAttempts
{
    public function __construct(private readonly LoginAttemptSecurity $security)
    {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $identifierField = 'email',
        string $audience = 'staff'
    ): Response {
        $rawIdentifier = $request->input($identifierField);

        // Always check an active lock BEFORE any authenticated-session shortcut.
        // This prevents a valid password from bypassing a cooldown on the same POST.
        if (is_scalar($rawIdentifier) && trim((string) $rawIdentifier) !== '') {
            $identifier = mb_strtolower(trim((string) $rawIdentifier));
            $ip = (string) ($request->ip() ?: 'unknown');
            $remaining = $this->security->remainingLockSeconds($identifier, $ip);

            if ($remaining > 0) {
                return $this->lockedResponse($request, $identifierField, $remaining);
            }
        } else {
            // The route-level throttle still protects malformed requests. Do not
            // create a lock against an empty or missing account identifier.
            if (Auth::check()) {
                return $this->redirectAuthenticatedUser();
            }

            return $next($request);
        }

        // A logged-in user should not be able to submit a second identity through
        // a login POST. They must sign out before switching accounts.
        if (Auth::check()) {
            return $this->redirectAuthenticatedUser();
        }

        $identifier = mb_strtolower(trim((string) $rawIdentifier));
        $ip = (string) ($request->ip() ?: 'unknown');
        $response = $next($request);

        // The login controllers call Auth::login() only after a valid password.
        if (Auth::check()) {
            $this->security->clearAfterSuccessfulLogin($identifier, $ip);
            return $response;
        }

        // Login controllers return redirects/validation responses for invalid
        // credentials. Never count server errors as password failures.
        if ($response->getStatusCode() < 500) {
            $result = $this->security->recordFailure($request, $identifier, $ip, $audience);
            $lockedSeconds = (int) ($result['locked_seconds'] ?? 0);

            if ($lockedSeconds > 0) {
                $response->headers->set('Retry-After', (string) $lockedSeconds);
                $request->session()->flash('error', $this->lockoutMessage($lockedSeconds));
                $request->session()->flash('login_lock_seconds', $lockedSeconds);
            }
        }

        return $response;
    }

    private function lockedResponse(Request $request, string $identifierField, int $seconds): Response
    {
        $message = $this->lockoutMessage($seconds);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', (string) $seconds);
        }

        return redirect()->back()
            ->withInput($request->only($identifierField))
            ->with('error', $message)
            ->with('login_lock_seconds', $seconds)
            ->header('Retry-After', (string) $seconds);
    }

    private function redirectAuthenticatedUser(): Response
    {
        $role = strtolower((string) Auth::user()?->role?->role_name);

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'cashier' => redirect()->route('cashier.dashboard'),
            'customer' => redirect()->route('web.shop'),
            default => redirect('/'),
        };
    }

    private function lockoutMessage(int $seconds): string
    {
        return __('Too many unsuccessful sign-in attempts. Please try again in :seconds seconds.', [
            'seconds' => $seconds,
        ]);
    }
}
