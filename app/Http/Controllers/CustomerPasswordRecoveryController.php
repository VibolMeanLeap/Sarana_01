<?php

namespace App\Http\Controllers;

use App\Mail\CustomerOtpMail;
use App\Mail\CustomerPasswordChangedMail;
use App\Models\CustomerPasswordResetOtp;
use App\Models\User;
use App\Services\TelegramBotService;
use App\Support\CustomerPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CustomerPasswordRecoveryController extends Controller
{
    private const SESSION_CHALLENGE_ID = 'customer_password_reset_challenge_id';
    private const SESSION_DELIVERY_METHOD = 'customer_password_reset_delivery_method';
    private const OTP_LIFETIME_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    public function requestForm(): View|RedirectResponse
    {
        if (auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer') {
            return redirect()->route('web.shop');
        }

        return view('website.login.forgot-password');
    }

    /** Send a password-reset OTP to either the verified Telegram chat or account email. */
    public function sendCode(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        $method = (string) $request->input('method', 'telegram');
        if (! in_array($method, ['email', 'telegram'], true)) {
            return back()->withErrors(['method' => 'Choose email or Telegram recovery.']);
        }
        // Keep compatibility with an older cached recovery form that does not
        // submit the new method selector; the legacy behavior was Telegram.
        $request->merge(['method' => $method]);

        $validated = $request->validate($method === 'email'
            ? ['method' => ['required', 'in:email,telegram'], 'email' => ['required', 'email', 'max:255']]
            : ['method' => ['required', 'in:email,telegram'], 'phone' => ['required', 'string', 'max:30']]
        );

        $request->session()->forget([self::SESSION_CHALLENGE_ID, self::SESSION_DELIVERY_METHOD]);
        $request->session()->put(self::SESSION_DELIVERY_METHOD, $method);
        $ipKey = 'customer-password-reset:ip:'.hash('sha256', (string) $request->ip());
        $limited = RateLimiter::tooManyAttempts($ipKey, 10);
        RateLimiter::hit($ipKey, 900);

        if ($method === 'email') {
            if (! $this->smtpConfigured()) {
                return back()->withErrors([
                    'email' => 'Email recovery is not available right now. Please choose Telegram recovery or contact the shop.',
                ]);
            }

            $email = mb_strtolower(trim((string) $validated['email']));
            $identityKey = 'customer-password-reset:email:'.hash('sha256', $email);
            $limited = $limited || RateLimiter::tooManyAttempts($identityKey, 3);
            RateLimiter::hit($identityKey, 900);

            if (! $limited && $this->smtpConfigured()) {
                $user = User::query()
                    ->where('status', 'active')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->whereHas('role', fn ($query) => $query->whereRaw('LOWER(role_name) = ?', ['customer']))
                    ->first();

                if ($user) {
                    $otp = (string) random_int(100000, 999999);
                    $challenge = $this->createChallenge($user, $otp);

                    try {
                        Mail::to($user->email)->send(new CustomerOtpMail(
                            $user->name,
                            $otp,
                            'password_reset',
                            self::OTP_LIFETIME_MINUTES
                        ));
                        $request->session()->put([
                            self::SESSION_CHALLENGE_ID => $challenge->id,
                            self::SESSION_DELIVERY_METHOD => 'email',
                        ]);
                    } catch (Throwable $exception) {
                        $challenge->forceFill(['consumed_at' => now()])->save();
                        Log::warning('Customer password reset email delivery failed.', [
                            'reset_id' => $challenge->id,
                            'exception' => $exception::class,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            }

            return redirect()->route('web.password.verify')->with(
                'recovery_notice',
                'If an active customer account uses that email address, a password reset code has been sent. Check your inbox and spam folder.'
            );
        }

        $rawPhone = trim((string) $validated['phone']);
        $normalized = CustomerPhoneNumber::normalize($rawPhone);
        $phoneKeyValue = $normalized !== '' ? $normalized : preg_replace('/\s+/', '', $rawPhone);
        $phoneKey = 'customer-password-reset:phone:'.hash('sha256', $phoneKeyValue);
        $limited = $limited || RateLimiter::tooManyAttempts($phoneKey, 3);
        RateLimiter::hit($phoneKey, 900);

        if (! $limited && $normalized !== '' && filled(config('services.telegram.bot_token'))) {
            // Only send to a Telegram chat already verified and stored on this
            // active customer account. Never accept a chat ID from the browser.
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
                $challenge = $this->createChallenge($user, $otp);

                try {
                    $telegram->sendMessage(
                        (string) $user->telegram_chat_id,
                        "SK Coffee password reset\n\nYour password reset code is: {$otp}\n\nEnter this six-digit code on the SK Coffee recovery page, then choose a new password. This code expires in five minutes and can be used once. Do not share it with anyone. If you did not request this, ignore this message."
                    );
                    $request->session()->put([
                        self::SESSION_CHALLENGE_ID => $challenge->id,
                        self::SESSION_DELIVERY_METHOD => 'telegram',
                    ]);
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
            'If an active customer account uses that phone number and has a verified Telegram account, a reset code has been sent to that Telegram chat. Check Telegram.'
        );
    }

    public function verifyForm(): View
    {
        return view('website.login.verify-password-reset', [
            'deliveryMethod' => session(self::SESSION_DELIVERY_METHOD, 'telegram'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $challengeId = $request->session()->get(self::SESSION_CHALLENGE_ID);
        $deliveryMethod = (string) $request->session()->get(self::SESSION_DELIVERY_METHOD, 'telegram');
        if (! is_numeric($challengeId) || ! in_array($deliveryMethod, ['email', 'telegram'], true)) {
            return back()->withErrors([
                'otp' => 'The code is invalid or expired. Request a new code and try again.',
            ]);
        }

        $result = DB::transaction(function () use ($challengeId, $validated, $deliveryMethod): array {
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

            if (! $user || strtolower((string) $user->role?->role_name) !== 'customer') {
                $challenge->forceFill(['consumed_at' => now()])->save();

                return ['error' => 'The account can no longer be recovered with this code. Contact the shop for assistance.'];
            }

            if ($deliveryMethod === 'telegram' && (! $user->telegram_chat_id || ! $user->telegram_verified_at)) {
                $challenge->forceFill(['consumed_at' => now()])->save();

                return ['error' => 'The account can no longer be recovered through Telegram. Contact the shop for assistance.'];
            }

            $user->password = $validated['password'];
            $user->remember_token = Str::random(60);
            if ($deliveryMethod === 'email' && ! $user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();

            $challenge->forceFill(['consumed_at' => now()])->save();
            CustomerPasswordResetOtp::query()
                ->where('user_id', $user->user_id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            return [
                'delivery_method' => $deliveryMethod,
                'chat_id' => (string) ($user->telegram_chat_id ?? ''),
                'email' => (string) $user->email,
                'name' => (string) $user->name,
            ];
        }, 3);

        if (isset($result['error'])) {
            return back()->withErrors(['otp' => $result['error']]);
        }

        $request->session()->forget([self::SESSION_CHALLENGE_ID, self::SESSION_DELIVERY_METHOD]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        if ($result['delivery_method'] === 'email') {
            try {
                Mail::to($result['email'])->send(new CustomerPasswordChangedMail($result['name']));
            } catch (Throwable $exception) {
                Log::warning('Customer password reset confirmation email failed.', [
                    'exception' => $exception::class,
                ]);
            }
        } else {
            try {
                app(TelegramBotService::class)->sendMessage(
                    $result['chat_id'],
                    'Your SK Coffee password was changed successfully. If you did not make this change, contact the shop immediately.'
                );
            } catch (Throwable $exception) {
                Log::warning('Customer password reset Telegram confirmation failed.', [
                    'exception' => $exception::class,
                ]);
            }
        }

        return redirect()->route('webLogin')->with(
            'status',
            'Your password has been updated. Please sign in with your new password.'
        );
    }

    private function createChallenge(User $user, string $otp): CustomerPasswordResetOtp
    {
        return DB::transaction(function () use ($user, $otp): CustomerPasswordResetOtp {
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
    }

    private function smtpConfigured(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && filled(config('mail.from.address'));
    }
}
