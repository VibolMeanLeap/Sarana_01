<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'offers';
    protected $primaryKey = 'offer_id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'discount',
        'new_price',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'new_price' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        // The admin form selects calendar dates (not times). Use date comparisons
        // so older rows saved as 00:00:00 remain valid through their listed end date.
        return $query->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString());
    }
}
