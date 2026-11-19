<?php

namespace App\Services;

use App\Mail\AdminGoogleLoginOtpMail;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminGoogleLoginOtpService
{
    private const TABLE = 'admin_google_login_otps';
    private const OTP_TTL_MINUTES = 5;
    private const CHALLENGE_TTL_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_RESENDS = 3;

    /** Create a short-lived challenge and send the first code. */
    public function issue(User $user): string
    {
        $this->assertEligibleAdmin($user);
        $this->assertSmtpConfigured();

        $challengeToken = bin2hex(random_bytes(32));
        $challengeHash = hash('sha256', $challengeToken);
        $code = $this->generateCode();
        $now = now();

        DB::transaction(function () use ($user, $challengeHash, $code, $now): void {
            // A fresh Google attempt invalidates any older, unfinished challenge for this admin.
            DB::table(self::TABLE)
                ->where('user_id', $user->user_id)
                ->whereNull('verified_at')
                ->delete();

            DB::table(self::TABLE)->insert([
                'user_id' => $user->user_id,
                'challenge_hash' => $challengeHash,
                'code_hash' => Hash::make($code),
                'otp_expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
                'challenge_expires_at' => $now->copy()->addMinutes(self::CHALLENGE_TTL_MINUTES),
                'attempts' => 0,
                'resend_count' => 0,
                'last_sent_at' => $now,
                'verified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        try {
            $this->sendCode($user, $code);
        } catch (Throwable $exception) {
            DB::table(self::TABLE)->where('challenge_hash', $challengeHash)->delete();
            Log::error('Unable to deliver administrator Google login OTP.', [
                'user_id' => $user->user_id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new DomainException('We could not send the verification email. Please check the Gmail SMTP settings or use your usual staff sign-in.');
        }

        return $challengeToken;
    }

    /** Return the pending active admin attached to a session challenge. */
    public function pendingUser(string $challengeToken): ?User
    {
        if ($challengeToken === '') {
            return null;
        }

        $row = DB::table(self::TABLE)
            ->where('challenge_hash', hash('sha256', $challengeToken))
            ->whereNull('verified_at')
            ->first();

        if (! $row || Carbon::parse($row->challenge_expires_at)->isPast()) {
            return null;
        }

        $user = User::query()->with('role')
            ->where('user_id', $row->user_id)
            ->where('status', 'active')
            ->first();

        if (! $user || strtolower((string) $user->role?->role_name) !== 'admin') {
            return null;
        }

        return $user;
    }

    /** Verify the submitted code. Returns a stable status for the controller. */
    public function verify(string $challengeToken, string $code): array
    {
        if ($challengeToken === '') {
            return ['status' => 'expired'];
        }

        $challengeHash = hash('sha256', $challengeToken);

        return DB::transaction(function () use ($challengeHash, $code): array {
            $row = DB::table(self::TABLE)
                ->where('challenge_hash', $challengeHash)
                ->lockForUpdate()
                ->first();

            if (! $row || $row->verified_at !== null) {
                return ['status' => 'expired'];
            }

            if (Carbon::parse($row->challenge_expires_at)->isPast()) {
                return ['status' => 'expired'];
            }

            if ((int) $row->attempts >= self::MAX_ATTEMPTS) {
                return ['status' => 'locked'];
            }

            if (Carbon::parse($row->otp_expires_at)->isPast()) {
                return ['status' => 'code_expired'];
            }

            if (! Hash::check($code, $row->code_hash)) {
                $attempts = (int) $row->attempts + 1;
                DB::table(self::TABLE)->where('id', $row->id)->update([
                    'attempts' => $attempts,
                    'updated_at' => now(),
                ]);

                return [
                    'status' => $attempts >= self::MAX_ATTEMPTS ? 'locked' : 'invalid',
                    'remaining' => max(0, self::MAX_ATTEMPTS - $attempts),
                ];
            }

            DB::table(self::TABLE)->where('id', $row->id)->update([
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

            return ['status' => 'ok', 'user_id' => (int) $row->user_id];
        }, 3);
    }

    /** Send a replacement code within the original 15-minute challenge window. */
    public function resend(string $challengeToken): void
    {
        if ($challengeToken === '') {
            throw new DomainException('Your verification session has expired. Please sign in with Google again.');
        }

        $challengeHash = hash('sha256', $challengeToken);
        $result = DB::transaction(function () use ($challengeHash): array {
            $row = DB::table(self::TABLE)
                ->where('challenge_hash', $challengeHash)
                ->lockForUpdate()
                ->first();

            if (! $row || $row->verified_at !== null || Carbon::parse($row->challenge_expires_at)->isPast()) {
                return ['error' => 'expired'];
            }

            if ((int) $row->resend_count >= self::MAX_RESENDS) {
                return ['error' => 'limit'];
            }

            $lastSentAt = Carbon::parse($row->last_sent_at);
            $elapsed = now()->getTimestamp() - $lastSentAt->getTimestamp();
            if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                return ['error' => 'cooldown', 'wait' => self::RESEND_COOLDOWN_SECONDS - $elapsed];
            }

            $user = User::query()->with('role')
                ->where('user_id', $row->user_id)
                ->where('status', 'active')
                ->first();

            if (! $user || strtolower((string) $user->role?->role_name) !== 'admin') {
                return ['error' => 'account'];
            }

            $code = $this->generateCode();
            $now = now();

            DB::table(self::TABLE)->where('id', $row->id)->update([
                'code_hash' => Hash::make($code),
                'otp_expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
                'attempts' => 0,
                'resend_count' => (int) $row->resend_count + 1,
                'last_sent_at' => $now,
                'updated_at' => $now,
            ]);

            return ['user' => $user, 'code' => $code];
        }, 3);

        if (isset($result['error'])) {
            $message = match ($result['error']) {
                'cooldown' => 'Please wait ' . (int) ($result['wait'] ?? 0) . ' seconds before requesting another code.',
                'limit' => 'You have reached the resend limit. Please start Google sign-in again.',
                'account' => 'This administrator account is no longer active. Please contact the system owner.',
                default => 'Your verification session has expired. Please sign in with Google again.',
            };
            throw new DomainException($message);
        }

        try {
            $this->assertSmtpConfigured();
            $this->sendCode($result['user'], $result['code']);
        } catch (Throwable $exception) {
            Log::error('Unable to resend administrator Google login OTP.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new DomainException('We could not send a new email code. Please check your mail connection and try again.');
        }
    }

    public function forget(string $challengeToken): void
    {
        if ($challengeToken !== '') {
            DB::table(self::TABLE)
                ->where('challenge_hash', hash('sha256', $challengeToken))
                ->delete();
        }
    }

    private function sendCode(User $user, string $code): void
    {
        Mail::to($user->email)->send(new AdminGoogleLoginOtpMail(
            code: $code,
            recipientName: (string) $user->name,
            expiresInMinutes: self::OTP_TTL_MINUTES,
        ));
    }

    private function assertEligibleAdmin(User $user): void
    {
        if (strtolower((string) $user->status) !== 'active'
            || strtolower((string) $user->role?->role_name) !== 'admin'
            || ! filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('This active administrator account does not have a valid email address for verification.');
        }
    }

    private function assertSmtpConfigured(): void
    {
        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp', []);

        if ($mailer !== 'smtp'
            || ! filled($smtp['host'] ?? null)
            || ! filled($smtp['username'] ?? null)
            || ! filled($smtp['password'] ?? null)
            || ! filled(config('mail.from.address'))) {
            throw new DomainException('Administrator email verification is not configured. Please set up Gmail SMTP in the .env file first.');
        }
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
