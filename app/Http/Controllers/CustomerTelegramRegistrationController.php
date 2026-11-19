<?php

namespace App\Http\Controllers;

use App\Models\CustomerTelegramRegistration;
use App\Models\Role;
use App\Services\CustomerTelegramOtpService;
use App\Support\CustomerPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class CustomerTelegramRegistrationController extends Controller
{
    private const SESSION_REGISTRATION_ID = 'customer_telegram_registration_id';
    private const SESSION_TELEGRAM_LINK = 'customer_telegram_registration_link';

    /** Create a short-lived registration draft. No users/customer row exists yet. */
    public function store(Request $request, CustomerTelegramOtpService $telegramOtp): RedirectResponse
    {
        if (! $telegramOtp->isConfigured()) {
            return back()->withInput()->withErrors([
                'register_error' => 'Telegram verification is not configured yet. Please contact the shop administrator.',
            ]);
        }

        // The IP throttle is applied on the route. These additional hashed keys
        // slow repeated attempts against the same phone number or email address.
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => trim((string) $request->input('phone', '')),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        foreach (['phone', 'email'] as $field) {
            $value = (string) $request->input($field, '');
            if ($field === 'phone') {
                $value = CustomerPhoneNumber::normalize($value);
            }
            if ($value === '') {
                continue;
            }

            $ipHash = hash('sha256', (string) $request->ip());
            $key = 'customer-registration:'.$field.':'.hash('sha256', $value).':'.$ipHash;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return back()->withInput()->withErrors([
                    $field => 'Too many registration attempts for this '.$field.'. Please try again later.',
                ]);
            }
            RateLimiter::hit($key, 3600);
        }

        // Expired temporary drafts are removed before a new registration is created.
        CustomerTelegramRegistration::query()
            ->where('created_at', '<', now()->subMinutes(30))
            ->delete();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (CustomerPhoneNumber::normalize((string) $value) === '') {
                        $fail('Enter a valid phone number. For a number outside Cambodia, include the + country prefix.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByUser((string) $value)) {
                        $fail('This phone number is still linked to an existing customer or staff account. Ask an administrator to update the old account or archive it to release the number.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByPendingRegistration((string) $value)) {
                        $fail('A Telegram registration is already pending for this phone number. Continue that verification or wait for the 30-minute registration draft to expire.');
                    }
                },
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('customer_telegram_registrations', 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->first();

        if (! $customerRole) {
            return back()->withInput()->withErrors([
                'register_error' => 'The customer role is missing. Please ask the administrator to check the database setup.',
            ]);
        }

        try {
            $registration = CustomerTelegramRegistration::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
            ]);

            $telegramLink = $telegramOtp->createTelegramLink($registration);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'register_error' => 'We could not start Telegram verification. Please check the Telegram setup and try again.',
            ]);
        }

        $request->session()->put([
            self::SESSION_REGISTRATION_ID => $registration->id,
            self::SESSION_TELEGRAM_LINK => $telegramLink,
        ]);

        return redirect()->route('web.register.telegram.verify');
    }

    /** Show the Telegram deep link and six-digit code entry form. */
    public function show(Request $request, CustomerTelegramOtpService $telegramOtp): View|RedirectResponse
    {
        $registration = $this->pendingRegistration($request);
        if (! $registration) {
            $this->clearPendingSession($request);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your registration session expired. Please create your account again.',
            ]);
        }

        $telegramLink = (string) $request->session()->get(self::SESSION_TELEGRAM_LINK, '');

        // Once Telegram has bound the chat, the link token is intentionally
        // consumed. Never regenerate a new link merely because the session's
        // display URL is missing; doing so would clear the valid chat binding.
        if (! $registration->telegram_chat_id) {
            $linkExpired = ! $registration->link_expires_at || $registration->link_expires_at->isPast();
            if ($telegramLink === '' || $linkExpired) {
                try {
                    $telegramLink = $telegramOtp->createTelegramLink($registration);
                    $request->session()->put(self::SESSION_TELEGRAM_LINK, $telegramLink);
                } catch (Throwable $exception) {
                    report($exception);

                    return redirect()->route('webRegister')->withErrors([
                        'register_error' => 'Telegram verification is temporarily unavailable. Please try again later.',
                    ]);
                }
            }
        } else {
            $telegramLink = '';
        }

        return view('website.register.verify-telegram', compact('registration', 'telegramLink'));
    }

    /** Consume the OTP, create the real active account, and sign the customer in. */
    public function verify(Request $request, CustomerTelegramOtpService $telegramOtp): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $registration = $this->pendingRegistration($request);
        if (! $registration) {
            $this->clearPendingSession($request);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your registration session expired. Please register again.',
            ]);
        }

        $user = $telegramOtp->verifyAndCreateCustomer((int) $registration->id, $validated['otp']);

        $this->clearPendingSession($request);
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('web.shop'))->with(
            'success',
            'Your Telegram verification is complete. Welcome to SK Coffee!'
        );
    }

    /** Generate a new one-use Telegram link if the previous link/code expired. */
    public function refreshLink(Request $request, CustomerTelegramOtpService $telegramOtp): RedirectResponse
    {
        $registration = $this->pendingRegistration($request);
        if (! $registration) {
            $this->clearPendingSession($request);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your registration session expired. Please register again.',
            ]);
        }

        $key = 'customer-telegram-link-refresh:'.$registration->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors([
                'telegram_link' => 'Too many new Telegram links were requested. Please wait a few minutes.',
            ]);
        }
        RateLimiter::hit($key, 300);

        try {
            $telegramLink = $telegramOtp->createTelegramLink($registration);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'telegram_link' => 'A new Telegram link could not be created. Please try again later.',
            ]);
        }

        $request->session()->put(self::SESSION_TELEGRAM_LINK, $telegramLink);

        return redirect()->route('web.register.telegram.verify')->with(
            'status',
            'A new Telegram link is ready. The previous link and code are no longer valid.'
        );
    }

    private function pendingRegistration(Request $request): ?CustomerTelegramRegistration
    {
        $id = $request->session()->get(self::SESSION_REGISTRATION_ID);
        if (! is_numeric($id)) {
            return null;
        }

        $registration = CustomerTelegramRegistration::query()->find((int) $id);
        if (! $registration || $registration->created_at?->lt(now()->subMinutes(30))) {
            return null;
        }

        return $registration;
    }

    private function clearPendingSession(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_REGISTRATION_ID,
            self::SESSION_TELEGRAM_LINK,
        ]);
    }
}
