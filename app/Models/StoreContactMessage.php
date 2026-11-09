<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function replies(): HasMany
    {
        return $this->hasMany(StoreContactReply::class, 'contact_message_id')->orderBy('created_at')->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }
}
