<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $class_session_id
 * @property int $student_id
 * @property int $schedule_id
 * @property int $devices_id
 * @property Carbon $registered_at
 * @property string $status
 * @property string $method
 */
class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_session_id',
        'student_id',
        'schedule_id',
        'devices_id',
        'registered_at',
        'status',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'devices_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'attendance_id');
    }

    public function justification(): HasOne
    {
        return $this->hasOne(Justification::class, 'attendance_id');
    }
}
