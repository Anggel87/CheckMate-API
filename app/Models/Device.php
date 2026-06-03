<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $mac_address
 * @property string|null $ip
 * @property bool $is_active
 * @property int $classroom_id
 */
class Device extends Model
{
    use HasFactory;

    protected $fillable = ['mac_address', 'ip', 'is_active', 'classroom_id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'devices_id');
    }
}
