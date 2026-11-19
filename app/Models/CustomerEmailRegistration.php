<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmailRegistration extends Model
{
    protected $table = 'customer_email_registrations';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password_hash',
        'otp_hash',
        'otp_expires_at',
        'draft_expires_at',
        'otp_attempts',
        'resend_count',
        'last_sent_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'draft_expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'consumed_at' => 'datetime',
            'otp_attempts' => 'integer',
            'resend_count' => 'integer',
        ];
    }
}
