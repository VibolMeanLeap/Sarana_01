<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';

    protected $primaryKey = 'purchase_id';

    // មិនប្រើ created_at និង updated_at
    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'product_id',
        'quantity',
        'cost_price',
        'purchase_date',
    ];

    // បម្លែង purchase_date ទៅជា date
    protected $casts = [
        'purchase_date' => 'date',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id',
            'supplier_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
            'product_id'
        );
    }

    public function getTotalCostAttribute()
    {
        return $this->quantity * $this->cost_price;
    }
}