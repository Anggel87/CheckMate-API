<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermissionOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'users_id',
        'permissions_id',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permissions_id');
    }
}
