<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $schedule_id
 * @property Carbon $date
 * @property int $teacher_id
 * @property int $device_id
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property string $status
 * @property string $opening_method
 * @property bool $is_active
 */
class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'date',
        'teacher_id',
        'device_id',
        'opened_at',
        'closed_at',
        'status',
        'opening_method',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_session_id');
    }
}
