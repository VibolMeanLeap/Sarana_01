<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Customer profiles available for a new POS selection.
     *
     * Login-linked customer profiles are selectable only while their user
     * account is active and has the customer role. Older/manual POS contact
     * profiles have no linked login account (user_id is NULL), so they remain
     * selectable because the customer table itself has no account status.
     */
    public function scopeActiveForPos(Builder $query): Builder
    {
        return $query->where(function (Builder $customerQuery): void {
            $customerQuery->whereNull('user_id')
                ->orWhereHas('user', function (Builder $userQuery): void {
                    $userQuery->where('status', 'active')
                        ->whereHas('role', function (Builder $roleQuery): void {
                            $roleQuery->whereRaw('LOWER(role_name) = ?', ['customer']);
                        });
                });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }
}
