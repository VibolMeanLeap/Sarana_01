<?php

namespace App\Http\Controllers;

use App\Mail\CustomerOtpMail;
use App\Models\Costumer;
use App\Models\CustomerEmailRegistration;
use App\Models\Role;
use App\Models\User;
use App\Support\CustomerPhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class CustomerEmailRegistrationController extends Controller
{
    private const SESSION_DRAFT_ID = 'customer_email_registration_id';
    private const OTP_LIFETIME_MINUTES = 5;
    private const DRAFT_LIFETIME_MINUTES = 30;
    private const MAX_ATTEMPTS = 5;
    private const MAX_RESENDS = 3;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function store(Request $request): RedirectResponse
    {
        if (auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer') {
            return redirect()->route('web.shop');
        }

        if (! $this->smtpConfigured()) {
            return back()->withInput()->withErrors([
                'register_error' => 'Email verification is temporarily unavailable. Please choose Telegram verification or contact the shop.',
            ]);
        }

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => trim((string) $request->input('phone', '')),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        CustomerEmailRegistration::query()
            ->where(function ($query): void {
                $query->where('draft_expires_at', '<', now())
                    ->orWhere('created_at', '<', now()->subMinutes(self::DRAFT_LIFETIME_MINUTES))
                    ->orWhereNotNull('consumed_at');
            })
            ->delete();

        $emailKey = 'customer-email-registration:email:'.hash('sha256', (string) $request->input('email'));
        $ipKey = 'customer-email-registration:ip:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($emailKey, 5) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return back()->withInput()->withErrors([
                'register_error' => 'Too many registration attempts. Please wait before trying again.',
            ]);
        }
        RateLimiter::hit($emailKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        $existingDraft = CustomerEmailRegistration::query()
            ->whereRaw('LOWER(email) = ?', [(string) $request->input('email')])
            ->where('draft_expires_at', '>', now())
            ->whereNull('consumed_at')
            ->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($existingDraft): void {
                    if (CustomerPhoneNumber::normalize((string) $value) === '') {
                        $fail('Enter a valid phone number. For a number outside Cambodia, include the + country prefix.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByUser((string) $value)) {
                        $fail('This phone number is already linked to an existing account. Please sign in or contact the shop.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByPendingRegistration(
                        (string) $value,
                        null,
                        $existingDraft ? (int) $existingDraft->id : null
                    )) {
                        $fail('A registration is already pending for this phone number. Continue that verification or wait for it to expire.');
                    }
                },
            ],
            'email' => [
                'required', 'email', 'max:255',
                'unique:users,email',
                'unique:customer_telegram_registrations,email',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customerRole = Role::query()->whereRaw('LOWER(role_name) = ?', ['customer'])->first();
        if (! $customerRole) {
            return back()->withInput()->withErrors([
                'register_error' => 'Customer registration is temporarily unavailable. Please contact the shop.',
            ]);
        }

        // A customer may retry an unfinished email registration from another tab.
        // Reuse the pending row for this email instead of making the address unusable.
        $otp = (string) random_int(100000, 999999);

        try {
            $draft = DB::transaction(function () use ($validated, $existingDraft, $otp): CustomerEmailRegistration {
                $draft = $existingDraft
                    ? CustomerEmailRegistration::query()->whereKey($existingDraft->id)->lockForUpdate()->firstOrFail()
                    : new CustomerEmailRegistration();

                $draft->fill([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'email' => mb_strtolower(trim($validated['email'])),
                    'password_hash' => Hash::make($validated['password']),
                    'otp_hash' => Hash::make($otp),
                    'otp_expires_at' => now()->addMinutes(self::OTP_LIFETIME_MINUTES),
                    'draft_expires_at' => now()->addMinutes(self::DRAFT_LIFETIME_MINUTES),
                    'otp_attempts' => 0,
                    'resend_count' => 0,
                    'last_sent_at' => now(),
                    'consumed_at' => null,
                ]);
                $draft->save();

                return $draft;
            });
        } catch (QueryException $exception) {
            Log::notice('Customer email registration could not reserve its email or phone.', [
                'exception' => $exception::class,
            ]);

            return back()->withInput()->withErrors([
                'register_error' => 'That email address or phone number is already being used by another registration. Please sign in or try again later.',
            ]);
        }

        $request->session()->put(self::SESSION_DRAFT_ID, $draft->id);

        try {
            Mail::to($draft->email)->send(new CustomerOtpMail(
                $draft->name,
                $otp,
                'email_verification',
                self::OTP_LIFETIME_MINUTES
            ));
        } catch (Throwable $exception) {
            $draft->forceFill(['last_sent_at' => now()->subSeconds(self::RESEND_COOLDOWN_SECONDS)])->save();
            Log::warning('Customer email registration OTP delivery failed.', [
                'registration_id' => $draft->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('web.register.email.verify')->withErrors([
                'email_otp' => 'We could not deliver the email code. Check the email address and SMTP setup, then try sending another code.',
            ]);
        }

        return redirect()->route('web.register.email.verify')->with(
            'status',
            'A six-digit verification code has been sent to your email address. It expires in five minutes.'
        );
    }

    public function show(Request $request): View|RedirectResponse
    {
        $draft = $this->pendingDraft($request);
        if (! $draft) {
            $request->session()->forget(self::SESSION_DRAFT_ID);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your email registration session expired. Please start again.',
            ]);
        }

        return view('website.register.verify-email', [
            'maskedEmail' => $this->maskEmail($draft->email),
            'resendAvailableAt' => $draft->last_sent_at?->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $draftId = $request->session()->get(self::SESSION_DRAFT_ID);
        if (! is_numeric($draftId)) {
            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your email registration session expired. Please start again.',
            ]);
        }

        $result = DB::transaction(function () use ($draftId, $validated): array {
            $draft = CustomerEmailRegistration::query()
                ->whereKey((int) $draftId)
                ->lockForUpdate()
                ->first();

            if (! $draft || $draft->consumed_at || $draft->draft_expires_at?->isPast()
                || $draft->otp_expires_at?->isPast() || (int) $draft->otp_attempts >= self::MAX_ATTEMPTS) {
                return ['error' => 'The code is invalid or expired. Request a new code and try again.'];
            }

            if (! Hash::check($validated['otp'], $draft->otp_hash)) {
                $draft->otp_attempts = (int) $draft->otp_attempts + 1;
                if ((int) $draft->otp_attempts >= self::MAX_ATTEMPTS) {
                    $draft->consumed_at = now();
                }
                $draft->save();

                return ['error' => 'The code is invalid or expired. Request a new code and try again.'];
            }

            if (User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($draft->email)])->exists()
                || CustomerPhoneNumber::isUsedByUser((string) $draft->phone)
                || CustomerPhoneNumber::isUsedByPendingRegistration((string) $draft->phone, null, (int) $draft->id)) {
                $draft->delete();

                return ['error' => 'This email address or phone number is no longer available. Please sign in or contact the shop.'];
            }

            $customerRole = Role::query()->whereRaw('LOWER(role_name) = ?', ['customer'])->first();
            if (! $customerRole) {
                return ['error' => 'Customer registration is temporarily unavailable. Please contact the shop.'];
            }

            $user = new User();
            $user->name = $draft->name;
            $user->phone = $draft->phone;
            $user->email = $draft->email;
            $user->username = $draft->phone;
            $user->password = $draft->password_hash;
            $user->role_id = $customerRole->role_id;
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->save();

            $existingWalkInProfile = Costumer::query()
                ->whereNull('user_id')
                ->orderByDesc('customer_id')
                ->get()
                ->first(fn (Costumer $profile): bool =>
                    CustomerPhoneNumber::normalize((string) $profile->phone)
                    === CustomerPhoneNumber::normalize((string) $draft->phone)
                );

            if ($existingWalkInProfile) {
                $existingWalkInProfile->user_id = $user->user_id;
                $existingWalkInProfile->name = $draft->name;
                $existingWalkInProfile->phone = $draft->phone;
                $existingWalkInProfile->save();
            } else {
                Costumer::create([
                    'user_id' => $user->user_id,
                    'name' => $draft->name,
                    'phone' => $draft->phone,
                ]);
            }

            $email = $draft->email;
            $draft->delete();

            return ['user_id' => (int) $user->user_id, 'email' => $email];
        }, 3);

        if (isset($result['error'])) {
            return back()->withErrors(['otp' => $result['error']]);
        }

        $user = User::query()->with('role')->findOrFail($result['user_id']);
        $request->session()->forget(self::SESSION_DRAFT_ID);
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('web.shop'))->with(
            'success',
            'Your email is verified and your SK Coffee account is ready. Welcome!'
        );
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! $this->smtpConfigured()) {
            return back()->withErrors(['email_otp' => 'Email delivery is not configured. Please use Telegram verification or contact the shop.']);
        }

        $draft = $this->pendingDraft($request);
        if (! $draft) {
            $request->session()->forget(self::SESSION_DRAFT_ID);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your email registration session expired. Please start again.',
            ]);
        }

        if ($draft->last_sent_at && $draft->last_sent_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            return back()->withErrors(['email_otp' => 'Please wait 60 seconds before requesting another code.']);
        }

        if ((int) $draft->resend_count >= self::MAX_RESENDS) {
            return back()->withErrors(['email_otp' => 'You have reached the maximum number of resend attempts. Please restart registration later.']);
        }

        $otp = (string) random_int(100000, 999999);
        $draft->forceFill([
            'otp_hash' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(self::OTP_LIFETIME_MINUTES),
            'otp_attempts' => 0,
            'resend_count' => (int) $draft->resend_count + 1,
            'last_sent_at' => now(),
        ])->save();

        try {
            Mail::to($draft->email)->send(new CustomerOtpMail(
                $draft->name,
                $otp,
                'email_verification',
                self::OTP_LIFETIME_MINUTES
            ));
        } catch (Throwable $exception) {
            Log::warning('Customer registration OTP resend failed.', [
                'registration_id' => $draft->id,
                'exception' => $exception::class,
            ]);

            return back()->withErrors(['email_otp' => 'The email could not be delivered. Please check your SMTP configuration and try again later.']);
        }

        return back()->with('status', 'A new verification code has been sent. The previous code is no longer valid.');
    }

    private function pendingDraft(Request $request): ?CustomerEmailRegistration
    {
        $id = $request->session()->get(self::SESSION_DRAFT_ID);
        if (! is_numeric($id)) {
            return null;
        }

        return CustomerEmailRegistration::query()
            ->whereKey((int) $id)
            ->whereNull('consumed_at')
            ->where('draft_expires_at', '>', now())
            ->first();
    }

    private function smtpConfigured(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && filled(config('mail.from.address'));
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, 1);
        return $visible.str_repeat('•', max(2, min(6, mb_strlen($name) - 1))).'@'.$domain;
    }
}
