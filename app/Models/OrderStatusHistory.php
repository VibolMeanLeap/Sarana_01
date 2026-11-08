<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';
    protected $primaryKey = 'history_id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'telegram_user_id',
        'telegram_username',
        'actor_label',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
