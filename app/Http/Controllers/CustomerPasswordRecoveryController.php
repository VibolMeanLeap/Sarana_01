<?php

namespace App\Http\Controllers;

use App\Models\CustomerPasswordResetOtp;
use App\Models\User;
use App\Services\TelegramBotService;
use App\Support\CustomerPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CustomerPasswordRecoveryController extends Controller
{
    private const SESSION_CHALLENGE_ID = 'customer_password_reset_challenge_id';
    private const OTP_LIFETIME_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    public function requestForm(): View|RedirectResponse
    {
        if (auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer') {
            return redirect()->route('web.shop');
        }

        return view('website.login.forgot-password');
    }

    /**
     * Always returns the same public response. A code is sent only to the
     * Telegram chat already verified and stored on the matching customer account.
     */
    public function sendCode(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $rawPhone = trim((string) $validated['phone']);
        $normalized = CustomerPhoneNumber::normalize($rawPhone);
        $phoneKeyValue = $normalized !== '' ? $normalized : preg_replace('/\s+/', '', $rawPhone);
        $phoneKey = 'customer-password-reset:phone:'.hash('sha256', $phoneKeyValue);
        $ipKey = 'customer-password-reset:ip:'.hash('sha256', (string) $request->ip());
        $limited = RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10);
        RateLimiter::hit($phoneKey, 900);
        RateLimiter::hit($ipKey, 900);

        $request->session()->forget(self::SESSION_CHALLENGE_ID);

        if (! $limited && $normalized !== '' && filled(config('services.telegram.bot_token'))) {
            // Telegram recovery is allowed only for an active customer whose
            // Telegram account was verified during registration. Never accept
            // a chat ID supplied by the browser.
            $candidates = User::query()
                ->with('role')
                ->where('status', 'active')
                ->whereNotNull('telegram_chat_id')
                ->whereNotNull('telegram_verified_at')
                ->whereHas('role', fn ($query) => $query->whereRaw('LOWER(role_name) = ?', ['customer']))
                ->get(['user_id', 'phone', 'role_id', 'telegram_chat_id', 'telegram_verified_at', 'status']);

            $user = $candidates->first(fn (User $candidate) =>
                CustomerPhoneNumber::normalize((string) $candidate->phone) === $normalized
            );

            if ($user) {
                $otp = (string) random_int(100000, 999999);
                $challenge = DB::transaction(function () use ($user, $otp): CustomerPasswordResetOtp {
                    CustomerPasswordResetOtp::query()
                        ->where('user_id', $user->user_id)
                        ->whereNull('consumed_at')
                        ->update(['consumed_at' => now(), 'updated_at' => now()]);

                    CustomerPasswordResetOtp::query()
                        ->where('expires_at', '<', now()->subDay())
                        ->delete();

                    return CustomerPasswordResetOtp::create([
                        'user_id' => $user->user_id,
                        'otp_hash' => Hash::make($otp),
                        'expires_at' => now()->addMinutes(self::OTP_LIFETIME_MINUTES),
                        'attempts' => 0,
                    ]);
                });

                try {
                    $telegram->sendMessage(
                        (string) $user->telegram_chat_id,
                        "SK Coffee password reset\n\nYour password reset code is: {$otp}\n\nEnter this six-digit code on the SK Coffee recovery page, then choose a new password. This code expires in five minutes and can be used once. Do not share it with anyone. If you did not request this, ignore this message."
                    );
                    $request->session()->put(self::SESSION_CHALLENGE_ID, $challenge->id);
                } catch (Throwable $exception) {
                    $challenge->forceFill(['consumed_at' => now()])->save();
                    Log::warning('Customer password reset Telegram delivery failed.', [
                        'reset_id' => $challenge->id,
                        'exception' => $exception::class,
                    ]);
                }
            }
        }

        return redirect()->route('web.password.verify')->with(
            'recovery_notice',
            'If an active SK Coffee customer account uses that phone number and has a verified Telegram account, a reset code has been sent to that Telegram chat. Check Telegram. For further help, contact the shop.'
        );
    }

    public function verifyForm(): View
    {
        return view('website.login.verify-password-reset');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $challengeId = $request->session()->get(self::SESSION_CHALLENGE_ID);
        if (! is_numeric($challengeId)) {
            return back()->withErrors([
                'otp' => 'The code is invalid or expired. Request a new code and try again.',
            ]);
        }

        $result = DB::transaction(function () use ($challengeId, $validated): array {
            $challenge = CustomerPasswordResetOtp::query()
                ->whereKey((int) $challengeId)
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->consumed_at || $challenge->expires_at?->isPast()
                || (int) $challenge->attempts >= self::MAX_ATTEMPTS) {
                return ['error' => 'The code is invalid or expired. Request a new code and try again.'];
            }

            if (! Hash::check($validated['otp'], $challenge->otp_hash)) {
                $challenge->attempts = (int) $challenge->attempts + 1;
                if ((int) $challenge->attempts >= self::MAX_ATTEMPTS) {
                    $challenge->consumed_at = now();
                }
                $challenge->save();

                return ['error' => 'The code is invalid or expired. Request a new code and try again.'];
            }

            $user = User::query()
                ->with('role')
                ->where('user_id', $challenge->user_id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $user || strtolower((string) $user->role?->role_name) !== 'customer'
                || ! $user->telegram_chat_id || ! $user->telegram_verified_at) {
                $challenge->forceFill(['consumed_at' => now()])->save();

                return ['error' => 'The account can no longer be recovered with this code. Contact the shop for assistance.'];
            }

            // User's hashed cast hashes the supplied plaintext password.
            $user->password = $validated['password'];
            // Invalidate remember-me cookies issued before the reset.
            $user->remember_token = Str::random(60);
            $user->save();

            $challenge->forceFill(['consumed_at' => now()])->save();
            CustomerPasswordResetOtp::query()
                ->where('user_id', $user->user_id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return ['chat_id' => (string) $user->telegram_chat_id];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['otp' => $result['error']]);
        }

        $request->session()->forget(self::SESSION_CHALLENGE_ID);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        try {
            app(TelegramBotService::class)->sendMessage(
                $result['chat_id'],
                "Your SK Coffee password was changed successfully. If you did not make this change, contact the shop immediately."
            );
        } catch (Throwable $exception) {
            Log::warning('Customer password reset confirmation could not be sent.', [
                'exception' => $exception::class,
            ]);
        }

        return redirect()->route('webLogin')->with(
            'status',
            'Your password has been updated. Please sign in with your new password.'
        );
    }
}
