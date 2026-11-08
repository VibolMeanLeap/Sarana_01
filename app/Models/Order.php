<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'order_id';

    // The legacy orders table has order_date but no created_at / updated_at.
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'cashier_id',
        'order_type_id',
        'waiting_num',
        'total_amount',
        'discount',
        'final_price',
        'exchange_rate',
        'payment_method_id',
        'is_paid',
        'payment_status',
        'order_date',
        'order_status',
        'order_source',
        'order_channel',
        'customer_note',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'order_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Costumer::class, 'customer_id', 'customer_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'user_id');
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class, 'order_type_id', 'order_type_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id', 'order_id');
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class, 'order_id', 'order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'order_id')
            ->orderBy('created_at')
            ->orderBy('history_id');
    }

    public function telegramNotification()
    {
        return $this->hasOne(TelegramOrderNotification::class, 'order_id', 'order_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'order_id', 'order_id');
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class, 'order_id', 'order_id');
    }
}
