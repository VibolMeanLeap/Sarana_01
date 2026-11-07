<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_method'; // គ្មាន s

    public $timestamps = false;
    protected $primaryKey = 'payment_method_id';

    protected $fillable = [
        'payment_name',
        // បន្ថែម column ដទៃទៀតតាម table ពិត បើមាន
    ];
}