<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCorrection extends Model
{
    protected $table = 'order_corrections';
    protected $primaryKey = 'correction_id';

    protected $fillable = [
        'order_id',
        'created_by',
        'reason',
        'notes',
        'before_snapshot',
        'after_snapshot',
        'original_total',
        'corrected_total',
        'difference_amount',
        'payment_adjustment_status',
        'payment_adjustment_type',
        'payment_adjustment_note',
        'payment_adjusted_by',
        'payment_adjusted_at',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
        'original_total' => 'decimal:2',
        'corrected_total' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'payment_adjusted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_adjusted_by', 'user_id');
    }
}
