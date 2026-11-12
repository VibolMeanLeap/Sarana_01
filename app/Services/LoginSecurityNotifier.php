<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginSecurityNotifier
{
    /**
     * Schedule a Telegram alert after the HTTP response has been sent.
     * Location is approximate IP geolocation, never GPS or a street address.
     */
    public function notify(
        Request $request,
        string $identifier,
        string $audience,
        int $failedAttempts,
        int $lockSeconds,
        string $scope
    ): void {
        if (! config('login_security.telegram_alerts', true)) {
            return;
        }

        $ip = (string) ($request->ip() ?: 'unknown');
        $ipHash = hash('sha256', $ip);
        $cooldownKey = 'login_security:telegram_alert:'.$ipHash;
        $cooldown = max(30, (int) config('login_security.telegram_alert_cooldown_seconds', 60));

        if (! Cache::add($cooldownKey, 1, $cooldown)) {
            return;
        }

        $telegram = app(TelegramBotService::class);
        if (! $telegram->isConfigured()) {
            Log::notice('Login security lockout occurred, but Telegram is not configured.', [
                'audience' => $audience,
                'source_ip' => $ip,
                'failed_attempts' => $failedAttempts,
                'lock_seconds' => $lockSeconds,
                'scope' => $scope,
            ]);
            return;
        }

        $audienceLabel = $audience === 'customer'
            ? 'Customer store login'
            : 'Staff login (Admin / Cashier)';

        $messageLines = [
            '🔐 COFFEE CORNER AURA — LOGIN SECURITY ALERT',
            '',
            'Possible credential-guessing activity was detected.',
            'Portal: '.$audienceLabel,
            'Account: '.$this->maskIdentifier($identifier),
            'Source IP: '.$ip,
        ];

        $location = $this->approximateIpLocation($ip);
        $messageLines[] = 'Approximate location: '.$location['label'];
        if ($location['map_url']) {
            $messageLines[] = 'Map: '.$location['map_url'];
        }
        $messageLines = array_merge($messageLines, [
            'Failed attempts: '.$failedAttempts,
            'Trigger: '.$scope,
            'Temporary lockout: '.$this->formatDuration($lockSeconds),
            'Time: '.now((string) config('login_security.timezone', 'Asia/Phnom_Penh'))->format('d M Y, H:i:s T'),
            '',
            'Location is IP-based and approximate (not GPS). VPNs, mobile networks and proxies may show an inaccurate area.',
            'This alert indicates repeated failed sign-ins; it does not by itself confirm a DDoS attack.',
        ]);
        $message = implode("\n", $messageLines);
        $chatId = (string) config('services.telegram.group_chat_id');

        // A Telegram or geolocation outage cannot undo the lockout or delay the browser response.
        app()->terminating(function () use ($telegram, $chatId, $message): void {
            try {
                $telegram->sendMessage($chatId, $message);
            } catch (Throwable $exception) {
                Log::warning('Login security Telegram alert could not be delivered.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    /** @return array{label:string,map_url:?string} */
    private function approximateIpLocation(string $ip): array
    {
        if (! config('login_security.geolocation_enabled', true)) {
            return ['label' => 'lookup disabled', 'map_url' => null];
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['label' => 'unavailable (private/reserved IP)', 'map_url' => null];
        }

        $cacheKey = 'login_security:geo:'.hash('sha256', $ip);
        $cached = Cache::remember($cacheKey, now()->addDays(7), function () use ($ip): array {
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(1)
                    ->timeout(2)
                    ->get('https://ipwho.is/'.$ip, [
                        'fields' => 'success,country,region,city,latitude,longitude',
                    ]);

                if (! $response->successful()) {
                    return ['label' => 'lookup unavailable', 'map_url' => null];
                }

                $data = $response->json();
                if (! is_array($data) || ! ($data['success'] ?? false)) {
                    return ['label' => 'lookup unavailable', 'map_url' => null];
                }

                $parts = array_values(array_filter([
                    $data['city'] ?? null,
                    $data['region'] ?? null,
                    $data['country'] ?? null,
                ], fn ($value) => is_string($value) && trim($value) !== ''));

                $latitude = $data['latitude'] ?? null;
                $longitude = $data['longitude'] ?? null;
                $mapUrl = is_numeric($latitude) && is_numeric($longitude)
                    ? 'https://www.google.com/maps?q='.rawurlencode((string) $latitude).','.rawurlencode((string) $longitude)
                    : null;

                return [
                    'label' => $parts !== [] ? implode(', ', $parts) : 'location unavailable',
                    'map_url' => $mapUrl,
                ];
            } catch (Throwable $exception) {
                Log::info('Login security IP geolocation lookup failed.', [
                    'exception' => $exception::class,
                ]);

                return ['label' => 'lookup unavailable', 'map_url' => null];
            }
        });

        return is_array($cached) && isset($cached['label'])
            ? ['label' => (string) $cached['label'], 'map_url' => isset($cached['map_url']) ? (string) $cached['map_url'] : null]
            : ['label' => 'lookup unavailable', 'map_url' => null];
    }

    private function maskIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if (str_contains($identifier, '@')) {
            [$local, $domain] = array_pad(explode('@', $identifier, 2), 2, '');
            $visible = mb_substr($local, 0, 1);
            return ($visible !== '' ? $visible : '*').'***@'.$domain;
        }

        $digits = preg_replace('/\D+/', '', $identifier) ?? '';
        if (strlen($digits) >= 6) {
            return '•••• '.substr($digits, -4);
        }

        $length = mb_strlen($identifier);
        if ($length <= 2) {
            return str_repeat('*', max(1, $length));
        }

        return mb_substr($identifier, 0, 2).str_repeat('*', min(8, $length - 2));
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' seconds';
        }

        $minutes = (int) ceil($seconds / 60);
        return $minutes.' minute'.($minutes === 1 ? '' : 's');
    }
}
