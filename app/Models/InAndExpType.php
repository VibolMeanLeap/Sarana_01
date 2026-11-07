<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InAndExpType extends Model
{
    protected $table = 'in_and_exp_types';
    protected $primaryKey = 'id_type';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function categories() { return $this->hasMany(AccountingCategory::class, 'id_type', 'id_type'); }
}