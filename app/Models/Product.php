<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
class Product extends Model
{
    use HasFactory;

    /**
     * Table & primary key match the existing "product" table structure
     * (product_id is AUTO_INCREMENT, not the Laravel default "id").
     */
    protected $table = 'product';
    protected $primaryKey = 'product_id';

    public $timestamps = false; 

    protected $fillable = [
        'product_name',
        'category_id',
        'supplier_id',
        'price',
        'qty',
        'product_type',
        'image',
        'description',
        'status',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'qty'   => 'integer',
        'cost'  => 'decimal:2',
    ];

    /**
     * Adjust the FK / model names below if your Category / Supplier
     * models use different keys.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }
}