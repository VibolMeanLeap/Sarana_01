<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreContactReply extends Model
{
    protected $table = 'store_contact_replies';

    protected $fillable = [
        'contact_message_id',
        'sender_type',
        'sender_user_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(StoreContactMessage::class, 'contact_message_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id', 'user_id');
    }
}
