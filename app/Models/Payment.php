<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id', 'provider', 'tran_id', 'amount', 'currency', 'status',
        'qr_string', 'qr_image', 'abapay_deeplink', 'app_store', 'play_store',
        'apv', 'bank_ref', 'expires_at', 'raw_response', 'callback_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'raw_response' => 'array',
        'callback_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
