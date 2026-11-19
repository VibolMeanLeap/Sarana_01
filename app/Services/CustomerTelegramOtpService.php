<?php

namespace App\Services;

use App\Models\Costumer;
use App\Models\CustomerTelegramRegistration;
use App\Models\Role;
use App\Models\User;
use App\Support\CustomerPhoneNumber;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CustomerTelegramOtpService
{
    private const LINK_LIFETIME_MINUTES = 15;
    private const OTP_LIFETIME_MINUTES = 5;
    private const MAX_OTP_ATTEMPTS = 5;

    /** Ask the Telegram user to share their own phone number before issuing an OTP. */
    public function phoneRequestKeyboard(): array
    {
        return [
            'keyboard' => [[
                ['text' => 'Share my phone number', 'request_contact' => true],
            ]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'input_field_placeholder' => 'Tap Share my phone number',
        ];
    }

    public function __construct(private readonly TelegramBotService $telegram)
    {
    }

    public function isConfigured(): bool
    {
        $username = ltrim(trim((string) config('services.telegram.bot_username')), '@');

        return filled(config('services.telegram.bot_token'))
            && filled(config('services.telegram.webhook_secret'))
            && preg_match('/^[A-Za-z0-9_]{5,32}$/', $username) === 1;
    }

    /** Create a fresh one-use Telegram deep link and invalidate any previous link/code. */
    public function createTelegramLink(CustomerTelegramRegistration $registration): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Telegram customer verification is not configured.');
        }

        $token = Str::random(48);
        $registration->forceFill([
            'telegram_chat_id' => null,
            'link_token_hash' => hash('sha256', $token),
            'link_expires_at' => now()->addMinutes(self::LINK_LIFETIME_MINUTES),
            'otp_hash' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'telegram_phone_verified_at' => null,
        ])->save();

        $username = ltrim(trim((string) config('services.telegram.bot_username')), '@');

        return 'https://t.me/'.$username.'?start='.$token;
    }

    /**
     * Consume a /start deep-link payload and bind the private Telegram chat to
     * the temporary registration. No OTP is sent until the user shares their
     * own Telegram phone contact and it matches the phone entered on the site.
     */
    public function handleTelegramStart(string $token, string $chatId): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Telegram customer verification is not fully configured.');
        }

        if (strlen($token) < 20 || strlen($token) > 80 || $chatId === '') {
            throw new DomainException('This registration link is invalid. Return to the website and generate a fresh Telegram link.');
        }

        $startResult = DB::transaction(function () use ($token, $chatId): array {
            CustomerTelegramRegistration::query()
                ->where('created_at', '<', now()->subMinutes(30))
                ->delete();

            $registration = CustomerTelegramRegistration::query()
                ->where('link_token_hash', hash('sha256', $token))
                ->where('link_expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            // Telegram can deliver /start more than once when a customer opens
            // the same deep link again. The link token is deliberately one-use,
            // but the chat binding is still valid for the 30-minute draft.
            // Resume that exact pending chat instead of reporting a false expiry.
            if (! $registration) {
                $alreadyBound = CustomerTelegramRegistration::query()
                    ->where('telegram_chat_id', $chatId)
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->lockForUpdate()
                    ->first();

                if ($alreadyBound) {
                    return [
                        'registration_id' => (int) $alreadyBound->id,
                        'already_bound' => true,
                    ];
                }

                if (User::query()->where('telegram_chat_id', $chatId)->exists()) {
                    throw new DomainException('This Telegram account is already connected to an existing customer account. Please sign in instead.');
                }

                throw new DomainException('This registration link has expired or was already used. Return to the website and generate a fresh Telegram link.');
            }

            if (User::query()->where('telegram_chat_id', $chatId)->exists()) {
                throw new DomainException('This Telegram account is already connected to an existing customer account. Please sign in instead.');
            }

            $chatAlreadyPending = CustomerTelegramRegistration::query()
                ->where('telegram_chat_id', $chatId)
                ->where('id', '<>', $registration->id)
                ->exists();

            if ($chatAlreadyPending) {
                throw new DomainException('This Telegram account is already connected to another pending registration. Return to the website and continue with the existing registration.');
            }

            $registration->forceFill([
                'telegram_chat_id' => $chatId,
                'link_token_hash' => null,
                'link_expires_at' => null,
                'telegram_phone_verified_at' => null,
                'otp_hash' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ])->save();

            return [
                'registration_id' => (int) $registration->id,
                'already_bound' => false,
            ];
        });

        $registration = CustomerTelegramRegistration::query()->find($startResult['registration_id']);
        if (! $registration) {
            throw new RuntimeException('The pending Telegram registration disappeared before it could be resumed.');
        }

        if ($startResult['already_bound']) {
            if ($registration->telegram_phone_verified_at) {
                $hasLiveOtp = $registration->otp_hash
                    && $registration->otp_expires_at
                    && $registration->otp_expires_at->isFuture()
                    && (int) $registration->otp_attempts < self::MAX_OTP_ATTEMPTS;

                $message = $hasLiveOtp
                    ? 'Your Telegram account is already connected and your phone number is confirmed. A valid OTP has already been sent. Please return to the SK Coffee website and enter that code. Do not request another code unless it expires.'
                    : 'Your Telegram account is connected and your phone number was already confirmed, but there is no active OTP. Return to the SK Coffee website and choose “Generate a new Telegram link” to restart verification.';

                $this->telegram->sendMessage($chatId, $message, ['remove_keyboard' => true]);
            } else {
                $this->telegram->sendMessage(
                    $chatId,
                    "Your SK Coffee registration is already connected to this Telegram account. Please share your own phone number using the button below. We will send an OTP only after it matches the number entered on the website.",
                    $this->phoneRequestKeyboard()
                );
            }

            return;
        }

        try {
            $this->telegram->sendMessage(
                $chatId,
                "Welcome to SK Coffee!\n\nBefore we send your verification code, please confirm that the phone number you entered on the website belongs to this Telegram account. Tap the button below to share your own phone number.\n\nWe will send an OTP only when the shared number matches your registration.",
                $this->phoneRequestKeyboard()
            );
        } catch (Throwable $exception) {
            // If the prompt could not be delivered, release the Telegram binding
            // and restore a short-lived one-use link so the customer can retry.
            CustomerTelegramRegistration::query()
                ->whereKey($startResult['registration_id'])
                ->where('telegram_chat_id', $chatId)
                ->update([
                    'telegram_chat_id' => null,
                    'link_token_hash' => hash('sha256', $token),
                    'link_expires_at' => now()->addMinutes(5),
                    'telegram_phone_verified_at' => null,
                    'otp_hash' => null,
                    'otp_expires_at' => null,
                    'otp_attempts' => 0,
                    'updated_at' => now(),
                ]);

            throw $exception;
        }
    }

    /**
     * Verify the contact belongs to the sender and matches the registration
     * phone, then issue the normal short-lived six-digit OTP.
     */
    public function handleTelegramContact(
        string $chatId,
        string $senderId,
        string $contactPhone,
        ?string $contactUserId
    ): void {
        if ($chatId === '' || $senderId === '' || ! hash_equals($chatId, $senderId)) {
            throw new RuntimeException('Please share your own phone number from the private chat with SK Coffee.');
        }

        // Telegram includes contact.user_id for a contact shared via the
        // request_contact button. Reject manually forwarded/other people's contacts.
        if ($contactUserId === null || $contactUserId === '' || ! hash_equals($senderId, $contactUserId)) {
            throw new RuntimeException('For security, please use the Share my phone number button. Sharing another person’s contact is not accepted.');
        }

        $otp = (string) random_int(100000, 999999);
        $result = DB::transaction(function () use ($chatId, $contactPhone, $otp): array {
            $registration = CustomerTelegramRegistration::query()
                ->where('telegram_chat_id', $chatId)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->lockForUpdate()
                ->first();

            if (! $registration) {
                return ['error' => 'No active registration is connected to this Telegram account. Return to the SK Coffee website and open a fresh connection link.'];
            }

            if ($registration->telegram_phone_verified_at) {
                return ['already_verified' => true];
            }

            $submittedPhone = $this->normalizePhoneNumber((string) $registration->phone);
            $sharedPhone = $this->normalizePhoneNumber($contactPhone);

            if ($submittedPhone === '' || $sharedPhone === '' || ! hash_equals($submittedPhone, $sharedPhone)) {
                // Do not leave the wrong Telegram account attached to this draft.
                // The visitor must start over with the correct phone number/account.
                $registration->delete();

                return ['phone_mismatch' => true];
            }

            $registration->forceFill([
                'telegram_phone_verified_at' => now(),
                'otp_hash' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(self::OTP_LIFETIME_MINUTES),
                'otp_attempts' => 0,
            ])->save();

            return ['otp' => $otp];
        });

        if (isset($result['error'])) {
            throw new RuntimeException($result['error']);
        }

        if ($result['phone_mismatch'] ?? false) {
            throw new RuntimeException('The phone number shared by Telegram does not match the number entered on the website. No OTP was sent and this pending registration was cancelled. Return to the website and register again using the phone number linked to this Telegram account.');
        }

        if ($result['already_verified'] ?? false) {
            $this->telegram->sendMessage(
                $chatId,
                'Your Telegram phone number is already confirmed. Please return to the SK Coffee website and enter the code already sent. If it expired, generate a fresh Telegram link to restart.'
            );

            return;
        }

        try {
            $this->telegram->sendMessage(
                $chatId,
                "Your SK Coffee verification code is: {$result['otp']}\n\nYour Telegram phone number matches the number entered on the website. Enter this six-digit code on the registration page. It expires in five minutes. Do not share this code with anyone.",
                ['remove_keyboard' => true]
            );
        } catch (Throwable $exception) {
            // Keep the phone match recorded, but clear the code; the customer can
            // request a fresh registration link from the browser to restart.
            CustomerTelegramRegistration::query()
                ->where('telegram_chat_id', $chatId)
                ->update([
                    'otp_hash' => null,
                    'otp_expires_at' => null,
                    'otp_attempts' => 0,
                    'updated_at' => now(),
                ]);

            throw $exception;
        }
    }

    /** Use the shared comparison format for website and Telegram contact numbers. */
    private function normalizePhoneNumber(string $phone): string
    {
        return CustomerPhoneNumber::normalize($phone);
    }

    /** Verify the code and create the real customer account only after success. */
    public function verifyAndCreateCustomer(int $registrationId, string $otp): User
    {
        $result = DB::transaction(function () use ($registrationId, $otp): array {
            $registration = CustomerTelegramRegistration::query()
                ->whereKey($registrationId)
                ->lockForUpdate()
                ->first();

            if (! $registration || $registration->created_at?->lt(now()->subMinutes(30))) {
                return ['error' => 'This registration has expired. Please register again.'];
            }

            if (! $registration->telegram_chat_id || ! $registration->telegram_phone_verified_at) {
                return ['error' => 'Share your own Telegram phone number first. The OTP is sent only after it matches your registration number.'];
            }

            if (! $registration->otp_hash || ! $registration->otp_expires_at) {
                return ['error' => 'Your phone number was confirmed, but no active code is available. Generate a new Telegram link and try again.'];
            }

            if ($registration->otp_expires_at->isPast()) {
                $registration->forceFill([
                    'otp_hash' => null,
                    'otp_expires_at' => null,
                ])->save();

                return ['error' => 'Your verification code has expired. Generate a new Telegram link and try again.'];
            }

            if ((int) $registration->otp_attempts >= self::MAX_OTP_ATTEMPTS) {
                return ['error' => 'Too many incorrect codes. Generate a new Telegram link to try again.'];
            }

            if (! Hash::check($otp, $registration->otp_hash)) {
                $attempts = (int) $registration->otp_attempts + 1;
                $registration->otp_attempts = $attempts;
                if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                    $registration->otp_hash = null;
                    $registration->otp_expires_at = null;
                }
                $registration->save();

                return ['error' => $attempts >= self::MAX_OTP_ATTEMPTS
                    ? 'Too many incorrect codes. Generate a new Telegram link to try again.'
                    : 'That verification code is not correct. Please check Telegram and try again.'];
            }

            $chatAlreadyUsed = User::query()
                ->where('telegram_chat_id', $registration->telegram_chat_id)
                ->exists();

            if ($chatAlreadyUsed) {
                return ['error' => 'This Telegram account is already connected to another customer account.'];
            }

            $accountAlreadyExists = CustomerPhoneNumber::isUsedByUser((string) $registration->phone)
                || User::query()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($registration->email)])
                    ->exists();

            if ($accountAlreadyExists) {
                $registration->delete();

                return ['error' => 'An account already uses this phone number or email address. Please sign in instead.'];
            }

            $customerRole = Role::query()
                ->whereRaw('LOWER(role_name) = ?', ['customer'])
                ->first();

            if (! $customerRole) {
                return ['error' => 'Customer registration is temporarily unavailable. Please contact the shop.'];
            }

            $user = User::create([
                'name' => $registration->name,
                'phone' => $registration->phone,
                'email' => $registration->email,
                'username' => $registration->phone,
                'password' => $registration->password_hash,
                'role_id' => $customerRole->role_id,
                'status' => 'active',
                'telegram_chat_id' => $registration->telegram_chat_id,
                'telegram_verified_at' => now(),
            ]);

            // If the same person previously existed as a walk-in/POS customer
            // without a login account, attach that existing profile so its
            // customer_id and historical orders are retained rather than
            // creating a second customer row for the same phone.
            $existingWalkInProfile = Costumer::query()
                ->whereNull('user_id')
                ->orderByDesc('customer_id')
                ->lockForUpdate()
                ->get()
                ->first(fn (Costumer $profile): bool =>
                    CustomerPhoneNumber::normalize((string) $profile->phone)
                    === CustomerPhoneNumber::normalize((string) $registration->phone)
                );

            if ($existingWalkInProfile) {
                $existingWalkInProfile->user_id = $user->user_id;
                $existingWalkInProfile->name = $registration->name;
                $existingWalkInProfile->phone = $registration->phone;
                $existingWalkInProfile->save();
            } else {
                Costumer::create([
                    'user_id' => $user->user_id,
                    'name' => $registration->name,
                    'phone' => $registration->phone,
                ]);
            }

            $registration->delete();

            return ['user' => $user];
        });

        if (isset($result['error'])) {
            throw ValidationException::withMessages([
                'otp' => $result['error'],
            ]);
        }

        return $result['user'];
    }
}
