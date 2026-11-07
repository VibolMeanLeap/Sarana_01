<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Costumer extends Model
{
    protected $table = 'customer';   // <-- បន្ទាត់នេះខ្វះពីមុន (សំខាន់បំផុត!)

    public $timestamps = false;
    protected $primaryKey = 'customer_id';
    protected $fillable = [
        'user_id','name','phone'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }
}
