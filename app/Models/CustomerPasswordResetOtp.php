<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPasswordResetOtp extends Model
{
    protected $table = 'customer_password_reset_otps';

    protected $fillable = [
        'user_id',
        'otp_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
