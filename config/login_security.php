<?php

return [
    'enabled' => (bool) env('LOGIN_SECURITY_ENABLED', true),
    'max_attempts' => (int) env('LOGIN_SECURITY_MAX_ATTEMPTS', 3),
    'window_seconds' => (int) env('LOGIN_SECURITY_WINDOW_SECONDS', 600),
    'pair_lockouts' => [30, 120, 600, 1800],
    'escalation_window_seconds' => 86400,

    'ip_max_attempts' => (int) env('LOGIN_SECURITY_IP_MAX_ATTEMPTS', 20),
    'ip_window_seconds' => (int) env('LOGIN_SECURITY_IP_WINDOW_SECONDS', 600),
    'ip_lockouts' => [120, 600, 1800, 3600],

    'telegram_alerts' => (bool) env('LOGIN_SECURITY_TELEGRAM_ALERTS', true),
    'telegram_alert_cooldown_seconds' => 60,
    'timezone' => env('LOGIN_SECURITY_TIMEZONE', 'Asia/Phnom_Penh'),

    // Uses city/region/country from IPWho.is. This is approximate IP geolocation,
    // not GPS. Set false to keep IP-only Telegram alerts.
    'geolocation_enabled' => (bool) env('LOGIN_SECURITY_GEOLOCATION', true),
];
