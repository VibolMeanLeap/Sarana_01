<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreContactMessage extends Model
{
    protected $table = 'store_contact_messages';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'order_reference',
        'topic',
        'message',
        'status',
        'admin_note',
        'read_at',
        'handled_by_user_id',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }
}
