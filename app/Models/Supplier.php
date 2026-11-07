<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';

    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address'
    ];
}
