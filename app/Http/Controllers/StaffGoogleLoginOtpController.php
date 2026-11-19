<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminGoogleLoginOtpService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffGoogleLoginOtpController extends Controller
{
    public function __construct(private readonly AdminGoogleLoginOtpService $otpService) {}

    public function show(Request $request): View|RedirectResponse
    {
        $challenge = (string) $request->session()->get('staff_google_otp_challenge', '');
        $user = $this->otpService->pendingUser($challenge);

        if (! $user) {
            $this->clearChallenge($request, $challenge);

            return redirect()->route('showlogin')
                ->with('error', 'Your administrator verification session expired. Please start Google sign-in again.');
        }

        return view('admin.auth.google-otp', [
            'maskedEmail' => $this->maskEmail((string) $user->email),
            'shopName' => config('app.name', 'SK Coffee'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'Enter the six-digit code from your email.',
        ]);

        $challenge = (string) $request->session()->get('staff_google_otp_challenge', '');
        $result = $this->otpService->verify($challenge, $validated['code']);

        if (($result['status'] ?? '') !== 'ok') {
            $message = match ($result['status'] ?? '') {
                'invalid' => 'That code is incorrect. ' . (int) ($result['remaining'] ?? 0) . ' attempt(s) remaining.',
                'code_expired' => 'That code has expired. Request a new code to continue.',
                'locked' => 'Too many incorrect attempts. Start Google sign-in again to request a new code.',
                default => 'Your verification session expired. Please sign in with Google again.',
            };

            if (in_array($result['status'] ?? '', ['expired', 'locked'], true)) {
                $this->clearChallenge($request, $challenge);
                return redirect()->route('showlogin')->with('error', $message);
            }

            return back()->withInput()->with('error', $message);
        }

        $user = User::query()->with('role')
            ->where('user_id', $result['user_id'])
            ->where('status', 'active')
            ->first();

        if (! $user || strtolower((string) $user->role?->role_name) !== 'admin') {
            $this->clearChallenge($request, $challenge);

            return redirect()->route('showlogin')
                ->with('error', 'This administrator account is no longer authorized to sign in.');
        }

        $this->otpService->forget($challenge);
        $request->session()->forget('staff_google_otp_challenge');
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('success_login', 'Email verification completed. Welcome back, administrator.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $challenge = (string) $request->session()->get('staff_google_otp_challenge', '');

        try {
            $this->otpService->resend($challenge);

            return redirect()->route('staff.google-otp.show')
                ->with('success', 'A new verification code has been sent to your registered email.');
        } catch (DomainException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'session has expired')) {
                $this->clearChallenge($request, $challenge);
                return redirect()->route('showlogin')->with('error', $exception->getMessage());
            }

            return redirect()->route('staff.google-otp.show')->with('error', $exception->getMessage());
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $challenge = (string) $request->session()->get('staff_google_otp_challenge', '');
        $this->clearChallenge($request, $challenge);

        return redirect()->route('showlogin')->with('success', 'Google administrator sign-in was cancelled.');
    }

    private function clearChallenge(Request $request, string $challenge): void
    {
        $this->otpService->forget($challenge);
        $request->session()->forget('staff_google_otp_challenge');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, 1);

        return $visible . str_repeat('*', max(3, mb_strlen($local) - 1)) . '@' . $domain;
    }
}
