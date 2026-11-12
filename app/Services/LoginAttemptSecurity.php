<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoginAttemptSecurity
{
    public function remainingLockSeconds(string $identifier, string $ip): int
    {
        if (! config('login_security.enabled', true)) {
            return 0;
        }

        $pairHash = $this->pairHash($identifier, $ip);
        $ipHash = hash('sha256', $ip);

        return max(
            $this->remainingForKey('login_security:pair_lock:'.$pairHash),
            $this->remainingForKey('login_security:ip_lock:'.$ipHash),
        );
    }

    /**
     * Record a failed password login. The identifier is always paired with the IP,
     * preventing three mistakes by one person from locking the account for everyone.
     * A separate IP-wide threshold catches attempts spread across many usernames.
     *
     * @return array{locked_seconds:int, failed_attempts:int, scope:string}
     */
    public function recordFailure(
        Request $request,
        string $identifier,
        string $ip,
        string $audience
    ): array {
        if (! config('login_security.enabled', true)) {
            return ['locked_seconds' => 0, 'failed_attempts' => 0, 'scope' => ''];
        }

        $pairHash = $this->pairHash($identifier, $ip);
        $ipHash = hash('sha256', $ip);
        $mutex = Cache::lock('login_security:mutex:'.$ipHash, 5);

        try {
            $result = $mutex->block(1, function () use ($request, $identifier, $ip, $audience, $pairHash, $ipHash): array {
                $alreadyLocked = max(
                    $this->remainingForKey('login_security:pair_lock:'.$pairHash),
                    $this->remainingForKey('login_security:ip_lock:'.$ipHash),
                );

                if ($alreadyLocked > 0) {
                    return ['locked_seconds' => $alreadyLocked, 'failed_attempts' => 0, 'scope' => ''];
                }

                $pairAttemptKey = 'login_security:pair_attempts:'.$pairHash;
                $pairEscalationKey = 'login_security:pair_escalation:'.$pairHash;
                $pairLockKey = 'login_security:pair_lock:'.$pairHash;
                $window = max(60, (int) config('login_security.window_seconds', 600));
                $maxAttempts = max(2, (int) config('login_security.max_attempts', 3));

                Cache::add($pairAttemptKey, 0, $window);
                $pairCount = (int) Cache::increment($pairAttemptKey);

                $ipAttemptKey = 'login_security:ip_attempts:'.$ipHash;
                $ipEscalationKey = 'login_security:ip_escalation:'.$ipHash;
                $ipLockKey = 'login_security:ip_lock:'.$ipHash;
                $ipWindow = max(60, (int) config('login_security.ip_window_seconds', 600));
                $ipMaxAttempts = max($maxAttempts, (int) config('login_security.ip_max_attempts', 20));

                Cache::add($ipAttemptKey, 0, $ipWindow);
                $ipCount = (int) Cache::increment($ipAttemptKey);

                $lockSeconds = 0;
                $scopes = [];

                if ($pairCount >= $maxAttempts) {
                    $level = (int) Cache::get($pairEscalationKey, 0) + 1;
                    $lockouts = (array) config('login_security.pair_lockouts', [30, 120, 600, 1800]);
                    $duration = $this->durationForLevel($lockouts, $level);

                    Cache::put($pairEscalationKey, $level, max(3600, (int) config('login_security.escalation_window_seconds', 86400)));
                    Cache::put($pairLockKey, now()->timestamp + $duration, $duration);
                    Cache::forget($pairAttemptKey);

                    $lockSeconds = max($lockSeconds, $duration);
                    $scopes[] = '3 failed attempts for the same account and source IP';
                }

                if ($ipCount >= $ipMaxAttempts) {
                    $level = (int) Cache::get($ipEscalationKey, 0) + 1;
                    $lockouts = (array) config('login_security.ip_lockouts', [120, 600, 1800, 3600]);
                    $duration = $this->durationForLevel($lockouts, $level);

                    Cache::put($ipEscalationKey, $level, max(3600, (int) config('login_security.escalation_window_seconds', 86400)));
                    Cache::put($ipLockKey, now()->timestamp + $duration, $duration);
                    Cache::forget($ipAttemptKey);

                    $lockSeconds = max($lockSeconds, $duration);
                    $scopes[] = 'source IP reached '.$ipMaxAttempts.' failed attempts in '.$ipWindow.' seconds';
                }

                if ($lockSeconds > 0) {
                    $scope = implode(' and ', $scopes);
                    app(LoginSecurityNotifier::class)->notify(
                        $request,
                        $identifier,
                        $audience,
                        max($pairCount, $ipCount),
                        $lockSeconds,
                        $scope,
                    );

                    return [
                        'locked_seconds' => $lockSeconds,
                        'failed_attempts' => max($pairCount, $ipCount),
                        'scope' => $scope,
                    ];
                }

                return ['locked_seconds' => 0, 'failed_attempts' => max($pairCount, $ipCount), 'scope' => ''];
            });
        } catch (LockTimeoutException) {
            // If many requests arrive simultaneously from one source, fail closed briefly.
            return ['locked_seconds' => 1, 'failed_attempts' => 0, 'scope' => 'concurrent login attempts'];
        }

        return is_array($result)
            ? $result
            : ['locked_seconds' => 1, 'failed_attempts' => 0, 'scope' => 'concurrent login attempts'];
    }

    public function clearAfterSuccessfulLogin(string $identifier, string $ip): void
    {
        $pairHash = $this->pairHash($identifier, $ip);

        foreach ([
            'login_security:pair_attempts:'.$pairHash,
            'login_security:pair_escalation:'.$pairHash,
            'login_security:pair_lock:'.$pairHash,
        ] as $key) {
            Cache::forget($key);
        }
    }

    private function pairHash(string $identifier, string $ip): string
    {
        $normalized = mb_strtolower(trim($identifier));
        return hash('sha256', $ip.'|'.$normalized);
    }

    private function remainingForKey(string $key): int
    {
        $until = (int) Cache::get($key, 0);
        $remaining = $until - now()->timestamp;

        if ($remaining <= 0 && $until > 0) {
            Cache::forget($key);
        }

        return max(0, $remaining);
    }

    private function durationForLevel(array $durations, int $level): int
    {
        if ($durations === []) {
            return 30;
        }

        $index = min(max(0, $level - 1), count($durations) - 1);
        return max(30, (int) $durations[$index]);
    }
}
