<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    protected $primaryKey = 'review_id';
    public $timestamps = false;

    protected $fillable = ['order_id', 'customer_id', 'rating', 'comment', 'is_approved'];

    protected $casts = ['rating' => 'integer', 'is_approved' => 'boolean'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Costumer::class, 'customer_id', 'customer_id');
    }
}
