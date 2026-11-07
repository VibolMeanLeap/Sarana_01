<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingCategory extends Model
{
    protected $table = 'accounting_category';

    protected $fillable = ['name', 'id_type'];

    // 💡 ថែមជួរកូដនេះ ដើម្បីប្រាប់ Laravel ថា Table នេះមិនប្រើ column 'created_at' និង 'updated_at' ទេ
    public $timestamps = false; 

    public function type()
    {
        return $this->belongsTo(InAndExpType::class, 'id_type', 'id_type');
    }

    public function transactions()
    {
        return $this->hasMany(CashTransaction::class, 'category_id');
    }
}