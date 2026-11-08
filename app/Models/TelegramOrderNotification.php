<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramOrderNotification extends Model
{
    protected $table = 'telegram_order_notifications';

    protected $fillable = [
        'order_id',
        'chat_id',
        'message_id',
        'status',
        'last_status',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
