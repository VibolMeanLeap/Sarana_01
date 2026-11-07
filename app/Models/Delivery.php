<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = 'delivery';
    protected $primaryKey = 'delivery_id';
    public $timestamps = false;
    protected $fillable = ['order_id', 'address', 'cost', 'status'];

    public function order() { return $this->belongsTo(Order::class, 'order_id', 'order_id'); }
}