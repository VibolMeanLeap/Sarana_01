<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Role;
use App\Models\User;
use App\Support\CustomerPhoneNumber;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CustomerGoogleProfileController extends Controller
{
    private const SESSION_KEY = 'customer_google_registration';
    private const SESSION_LIFETIME_SECONDS = 900;

    public function show(Request $request): View|RedirectResponse
    {
        $profile = $this->pendingProfile($request);
        if (! $profile) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your Google registration session expired. Please continue with Google again.',
            ]);
        }

        return view('website.register.google-profile-complete', [
            'googleName' => (string) ($profile['name'] ?? ''),
            'googleEmail' => (string) $profile['email'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->pendingProfile($request);
        if (! $profile) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('webRegister')->withErrors([
                'register_error' => 'Your Google registration session expired. Please continue with Google again.',
            ]);
        }

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => trim((string) $request->input('phone', '')),
        ]);

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
                        $fail('This phone number is already linked to another account. Please sign in with that account or contact the shop.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByPendingRegistration((string) $value)) {
                        $fail('This phone number is reserved by a pending Telegram registration. Complete that registration or try again after it expires.');
                    }
                },
            ],
        ]);

        try {
            $user = DB::transaction(function () use ($profile, $validated): User {
                $googleId = (string) $profile['google_id'];
                $email = mb_strtolower(trim((string) $profile['email']));
                $name = trim((string) $validated['name']);
                $phone = trim((string) $validated['phone']);

                // Re-check identity and phone under a transaction in case another
                // tab completed registration after this profile page was opened.
                $existing = User::query()
                    ->where('google_id', $googleId)
                    ->lockForUpdate()
                    ->first();

                if (! $existing) {
                    $existing = User::query()
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->lockForUpdate()
                        ->first();
                }

                if ($existing) {
                    if (strtolower((string) $existing->status) !== 'active'
                        || strtolower((string) $existing->role()->value('role_name')) !== 'customer') {
                        throw new DomainException('This Google email is already connected to an account that cannot register as a customer. Please contact the shop.');
                    }

                    $linkedGoogleId = (string) ($existing->google_id ?? '');
                    if ($linkedGoogleId !== '' && ! hash_equals($linkedGoogleId, $googleId)) {
                        throw new DomainException('This customer email is already linked to another Google account. Please sign in using its original method.');
                    }

                    $existing->google_id = $googleId;
                    if (! $existing->email_verified_at) {
                        $existing->email_verified_at = now();
                    }
                    $existing->save();
                    if (! $existing->customer()->exists()) {
                        Costumer::query()->firstOrCreate(
                            ['user_id' => $existing->user_id],
                            ['name' => $existing->name, 'phone' => $existing->phone]
                        );
                    }

                    return $existing->load('role');
                }

                if (CustomerPhoneNumber::isUsedByUser($phone)
                    || CustomerPhoneNumber::isUsedByPendingRegistration($phone)) {
                    throw new DomainException('This phone number has just been reserved by another account or pending registration. Please use a different number.');
                }

                $customerRole = Role::query()
                    ->whereRaw('LOWER(role_name) = ?', ['customer'])
                    ->lockForUpdate()
                    ->first();

                if (! $customerRole) {
                    throw new DomainException('Customer registration is temporarily unavailable. Please contact the shop.');
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'phone' => $phone,
                    'username' => $phone,
                    // Google owns the initial sign-in. A customer can set a local
                    // password later through the email OTP password-recovery flow.
                    'password' => Str::random(64),
                    'role_id' => $customerRole->role_id,
                    'status' => 'active',
                ]);
                $user->email_verified_at = now();
                $user->save();

                // Preserve an existing walk-in/POS customer profile and order
                // history when its phone matches this new account.
                $walkInProfile = Costumer::query()
                    ->whereNull('user_id')
                    ->lockForUpdate()
                    ->get()
                    ->first(fn (Costumer $candidate): bool =>
                        CustomerPhoneNumber::normalize((string) $candidate->phone)
                        === CustomerPhoneNumber::normalize($phone)
                    );

                if ($walkInProfile) {
                    $walkInProfile->user_id = $user->user_id;
                    $walkInProfile->name = $name;
                    $walkInProfile->phone = $phone;
                    $walkInProfile->save();
                } else {
                    Costumer::create([
                        'user_id' => $user->user_id,
                        'name' => $name,
                        'phone' => $phone,
                    ]);
                }

                return $user->load('role');
            }, 3);

            $request->session()->forget(self::SESSION_KEY);
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('web.shop'))->with(
                'success',
                'Welcome to SK Coffee! Your Google customer account is ready.'
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['phone' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Log::warning('Google customer profile completion failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'phone' => 'We could not finish creating your customer account. Please try again or contact the shop.',
            ]);
        }
    }

    private function pendingProfile(Request $request): ?array
    {
        $profile = $request->session()->get(self::SESSION_KEY);
        if (! is_array($profile)
            || empty($profile['google_id'])
            || empty($profile['email'])
            || ! filter_var($profile['email'], FILTER_VALIDATE_EMAIL)
            || (int) ($profile['created_at'] ?? 0) < time() - self::SESSION_LIFETIME_SECONDS) {
            return null;
        }

        return $profile;
    }
}
