<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
      protected $table = 'settings';

    protected $fillable = [
        'shop_name',
        'logo',
        'address',
        'tel',
        'exchange_rate',
        'delivery_fee',
    ];

    public $timestamps = false;
}
