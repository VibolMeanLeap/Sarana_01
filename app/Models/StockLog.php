<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    use HasFactory;

    protected $table = 'stock_logs';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'product_id',
        'user_id',
        'log_date',
        'type',
        'quantity',
        'reason',
        'note',
    ];

    protected $casts = [
        'log_date' => 'datetime',
        'quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
