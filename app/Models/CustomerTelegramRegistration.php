<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTelegramRegistration extends Model
{
    protected $table = 'customer_telegram_registrations';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password_hash',
        'telegram_chat_id',
        'telegram_phone_verified_at',
        'link_token_hash',
        'link_expires_at',
        'otp_hash',
        'otp_expires_at',
        'otp_attempts',
    ];

    protected function casts(): array
    {
        return [
            'link_expires_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'telegram_phone_verified_at' => 'datetime',
            'otp_attempts' => 'integer',
        ];
    }
}
