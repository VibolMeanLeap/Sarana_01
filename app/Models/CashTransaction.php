<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $table = 'cash_transactions';

    public $timestamps = false;

    protected $fillable = [
        'category_id', 'amount', 'transaction_date',
        'order_id', 'purchase_id', 'user_id', 'note',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function category() { return $this->belongsTo(AccountingCategory::class, 'category_id'); }
    public function order() { return $this->belongsTo(Order::class, 'order_id', 'order_id'); }
    public function purchase() { return $this->belongsTo(Purchase::class, 'purchase_id', 'purchase_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }

    public function getIsIncomeAttribute(): bool
    {
        return $this->category?->type?->id_type === 1;
    }
}