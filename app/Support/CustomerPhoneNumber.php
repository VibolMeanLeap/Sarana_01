<?php

namespace App\Support;

use App\Models\CustomerTelegramRegistration;
use App\Models\User;

/**
 * Normalizes customer phone values for comparisons without changing the
 * human-readable value stored in existing customer records.
 *
 * Cambodia is the default national format. International numbers must include
 * a leading + or 00 prefix; local numbers are interpreted using the configured
 * default calling code.
 */
class CustomerPhoneNumber
{
    public static function normalize(string $phone): string
    {
        $raw = trim($phone);
        $digits = preg_replace('/\\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return '';
        }

        $countryCode = preg_replace('/\\D+/', '', (string) config('services.telegram.phone_country_code', '855')) ?? '855';
        if ($countryCode === '') {
            return '';
        }

        // Preserve an explicit international prefix, while normalizing an
        // optional trunk zero after the configured country calling code.
        if (str_starts_with($raw, '+') || str_starts_with($digits, '00')) {
            $international = str_starts_with($raw, '+') ? $digits : substr($digits, 2);
            if (str_starts_with($international, $countryCode)) {
                $nationalPart = substr($international, strlen($countryCode));
                if (str_starts_with($nationalPart, '0')) {
                    $nationalPart = substr($nationalPart, 1);
                }
                $international = $countryCode.$nationalPart;
            }

            return strlen($international) >= 8 && strlen($international) <= 15 ? $international : '';
        }

        if (str_starts_with($digits, $countryCode)) {
            $nationalPart = substr($digits, strlen($countryCode));
            if (str_starts_with($nationalPart, '0')) {
                $nationalPart = substr($nationalPart, 1);
            }
            $normalized = $countryCode.$nationalPart;

            return strlen($normalized) >= 8 && strlen($normalized) <= 15 ? $normalized : '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        $normalized = $countryCode.$digits;

        return strlen($normalized) >= 8 && strlen($normalized) <= 15 ? $normalized : '';
    }

    public static function isUsedByUser(string $phone, ?int $exceptUserId = null): bool
    {
        $target = self::normalize($phone);
        if ($target === '') {
            return false;
        }

        $users = User::query()
            ->when($exceptUserId !== null, fn ($query) => $query->where('user_id', '<>', $exceptUserId))
            ->get(['user_id', 'phone', 'username']);

        foreach ($users as $user) {
            if (self::normalize((string) $user->phone) === $target
                || self::normalize((string) $user->username) === $target) {
                return true;
            }
        }

        return false;
    }

    public static function isUsedByPendingRegistration(
        string $phone,
        ?int $exceptTelegramRegistrationId = null,
        ?int $exceptEmailRegistrationId = null
    ): bool {
        $target = self::normalize($phone);
        if ($target === '') {
            return false;
        }

        $telegramRegistrations = CustomerTelegramRegistration::query()
            ->where('created_at', '>=', now()->subMinutes(30))
            ->when($exceptTelegramRegistrationId !== null, fn ($query) => $query->where('id', '<>', $exceptTelegramRegistrationId))
            ->get(['id', 'phone']);

        foreach ($telegramRegistrations as $registration) {
            if (self::normalize((string) $registration->phone) === $target) {
                return true;
            }
        }

        // Email OTP and Telegram OTP are separate verification channels, but a
        // phone number can only be reserved by one pending registration at a time.
        if (class_exists(\App\Models\CustomerEmailRegistration::class)
            && \Illuminate\Support\Facades\Schema::hasTable('customer_email_registrations')) {
            $emailRegistrations = \App\Models\CustomerEmailRegistration::query()
                ->where('draft_expires_at', '>', now())
                ->whereNull('consumed_at')
                ->when($exceptEmailRegistrationId !== null, fn ($query) => $query->where('id', '<>', $exceptEmailRegistrationId))
                ->get(['id', 'phone']);

            foreach ($emailRegistrations as $registration) {
                if (self::normalize((string) $registration->phone) === $target) {
                    return true;
                }
            }
        }

        return false;
    }
}
