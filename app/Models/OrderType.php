<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderType extends Model
{
    protected $table = 'order_type'; // គ្មាន s

    public $timestamps = false;
    protected $primaryKey = 'order_type_id';

    protected $fillable = [
        'order_name',
    ];
}