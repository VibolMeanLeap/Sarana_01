<?php

namespace App\Http\Controllers;

use App\Models\User;
use DomainException;
use Google\Client as GoogleClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Start the Google OpenID Connect authorization-code flow.
     * The audience is deliberately limited to staff or customer login pages.
     */
    public function redirect(Request $request, string $audience): RedirectResponse
    {
        if (! in_array($audience, ['staff', 'customer'], true)) {
            abort(404);
        }

        if (Auth::check()) {
            return $this->redirectAuthenticatedUser(Auth::user());
        }

        if (! $this->isConfigured()) {
            return $this->loginPage($audience)->with('error', 'Google Sign-In is not configured yet. Please use your usual login or finish the Google setup.');
        }

        try {
            $client = $this->makeGoogleClient();
            $state = bin2hex(random_bytes(32));
            $nonce = bin2hex(random_bytes(32));

            // The Google PHP client generates and retains a PKCE verifier on its OAuth2 service.
            $codeVerifier = $client->getOAuth2Service()->generateCodeVerifier();
            $client->setState($state);
            $client->setAccessType('online');
            $client->setIncludeGrantedScopes(true);
            $client->setScopes(['openid', 'email', 'profile']);

            // Support more than one Google login tab in the same browser session.
            $flows = (array) $request->session()->get('google_auth_flows', []);
            $now = time();
            foreach ($flows as $savedState => $savedFlow) {
                if (! is_array($savedFlow) || (int) ($savedFlow['created_at'] ?? 0) < $now - 600) {
                    unset($flows[$savedState]);
                }
            }

            $flows[$state] = [
                'audience' => $audience,
                'nonce' => $nonce,
                'code_verifier' => $codeVerifier,
                'created_at' => $now,
            ];

            // Keep the session small if someone starts several OAuth flows.
            if (count($flows) > 5) {
                $flows = array_slice($flows, -5, null, true);
            }

            $request->session()->put('google_auth_flows', $flows);

            return redirect()->away($client->createAuthUrl(null, ['nonce' => $nonce]));
        } catch (Throwable $exception) {
            Log::warning('Unable to start Google Sign-In.', [
                'audience' => $audience,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->loginPage($audience)->with('error', 'Google Sign-In could not be started. Please try again or use your usual login.');
        }
    }

    /** Complete the Google callback, verify the ID token, and start the local session. */
    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $flows = (array) $request->session()->get('google_auth_flows', []);
        $flow = $state !== '' ? ($flows[$state] ?? null) : null;

        if ($state !== '') {
            unset($flows[$state]);
            $request->session()->put('google_auth_flows', $flows);
        }

        if (! is_array($flow)
            || ! in_array($flow['audience'] ?? null, ['staff', 'customer'], true)
            || (int) ($flow['created_at'] ?? 0) < time() - 600
            || empty($flow['code_verifier'])
            || empty($flow['nonce'])) {
            return $this->loginPage('customer')->with('error', 'Your Google sign-in session expired or could not be verified. Please try again.');
        }

        $audience = $flow['audience'];

        if ($request->filled('error')) {
            return $this->loginPage($audience)->with('error', 'Google sign-in was cancelled. You can try again or use your usual login.');
        }

        if (! $request->filled('code') || ! $this->isConfigured()) {
            return $this->loginPage($audience)->with('error', 'Google did not return a valid sign-in response. Please try again.');
        }

        try {
            $client = $this->makeGoogleClient();
            $token = $client->fetchAccessTokenWithAuthCode(
                (string) $request->query('code'),
                (string) $flow['code_verifier']
            );

            if (! is_array($token) || isset($token['error']) || empty($token['id_token'])) {
                Log::warning('Google authorization-code exchange failed.', [
                    'error' => is_array($token) ? ($token['error'] ?? 'missing_id_token') : 'invalid_token_response',
                ]);

                return $this->loginPage($audience)->with('error', 'Google could not complete sign-in. Please try again.');
            }

            $client->setAccessToken($token);
            $claims = $client->verifyIdToken();

            if (! is_array($claims)
                || ! $this->validGoogleClaims($claims, (string) $flow['nonce'])) {
                return $this->loginPage($audience)->with('error', 'Google identity verification failed. Please try again.');
            }

            $googleId = (string) $claims['sub'];
            $email = mb_strtolower(trim((string) $claims['email']));

            $user = $this->findAndLinkLocalAccount($googleId, $email, $audience);

            // Google sign-in opts into Laravel's persistent remember cookie.
            // No Google access or refresh token is stored by this application.
            Auth::login($user, true);
            $request->session()->regenerate();

            if ($audience === 'customer') {
                return redirect()->intended(route('web.shop'))->with('success', 'Welcome back. You are signed in with Google.');
            }

            if (strtolower((string) $user->role?->role_name) === 'admin') {
                return redirect()->route('admin.dashboard')->with('success_login', 'Welcome back, administrator.');
            }

            return redirect()->route('cashier.dashboard')->with('success_login', 'Welcome back. Your cashier workspace is ready.');
        } catch (DomainException $exception) {
            return $this->loginPage($audience)->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::warning('Google sign-in callback failed.', [
                'audience' => $audience,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->loginPage($audience)->with('error', 'Google sign-in could not be completed. Check the Google credentials and redirect URI, then try again.');
        }
    }

    private function makeGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('services.google.client_id'));
        $client->setClientSecret((string) config('services.google.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes(['openid', 'email', 'profile']);

        return $client;
    }

    private function redirectUri(): string
    {
        $configured = trim((string) config('services.google.redirect_uri'));

        return $configured !== '' ? $configured : route('google.callback');
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /**
     * verifyIdToken() validates Google's JWT signature, issuer, audience and expiry.
     * The nonce and verified-email checks bind the identity to this browser flow.
     */
    private function validGoogleClaims(array $claims, string $expectedNonce): bool
    {
        $issuer = (string) ($claims['iss'] ?? '');
        $email = trim((string) ($claims['email'] ?? ''));
        $emailVerified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $issuedAt = (int) ($claims['iat'] ?? 0);

        return in_array($issuer, ['https://accounts.google.com', 'accounts.google.com'], true)
            && hash_equals($expectedNonce, (string) ($claims['nonce'] ?? ''))
            && ! empty($claims['sub'])
            && strlen((string) $claims['sub']) <= 255
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && $emailVerified
            && $issuedAt > 0
            && $issuedAt <= time() + 120;
    }

    /**
     * Link only an existing active Sarona account. A Google account cannot
     * create an admin/cashier user or change a user's existing application role.
     */
    private function findAndLinkLocalAccount(string $googleId, string $email, string $audience): User
    {
        return DB::transaction(function () use ($googleId, $email, $audience): User {
            $user = User::query()
                ->where('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->lockForUpdate()
                    ->first();
            }

            if (! $user) {
                throw new DomainException('No Sarona account uses this verified Google email. Create your account first, then sign in with Google.');
            }

            if (strtolower((string) $user->status) !== 'active') {
                throw new DomainException('This Sarona account is inactive. Please contact the administrator.');
            }

            $roleName = strtolower((string) $user->role()->value('role_name'));
            $allowedRoles = $audience === 'staff' ? ['admin', 'cashier'] : ['customer'];

            if (! in_array($roleName, $allowedRoles, true)) {
                $message = $audience === 'staff'
                    ? 'This Google account is not linked to an active staff account. Use the customer sign-in page instead.'
                    : 'This Google account is not linked to a customer account. Use the staff sign-in page instead.';

                throw new DomainException($message);
            }

            $existingGoogleId = (string) ($user->google_id ?? '');
            if ($existingGoogleId !== '' && ! hash_equals($existingGoogleId, $googleId)) {
                throw new DomainException('This Sarona account is already linked to a different Google account. Contact the administrator to change the link safely.');
            }

            $user->google_id = $googleId;
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();

            return $user->load('role');
        }, 3);
    }

    private function loginPage(string $audience): RedirectResponse
    {
        return redirect()->route($audience === 'staff' ? 'showlogin' : 'webLogin');
    }

    private function redirectAuthenticatedUser(User $user): RedirectResponse
    {
        $role = strtolower((string) $user->role?->role_name);

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'cashier' => redirect()->route('cashier.dashboard'),
            default => redirect()->route('web.shop'),
        };
    }
}
