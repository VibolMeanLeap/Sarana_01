<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';
    protected $primaryKey = 'category_id';

    // ថែមបន្ទាត់មួយនេះដើម្បីប្រាប់ Laravel ថាមិនបាច់ប្រើ Field created_at និង updated_at ឡើយ
    public $timestamps = false; 

    protected $fillable = [
        'category_name',
        'description'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
    
}