<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $present_tolerance_minutes
 * @property int $late_tolerance_minutes
 * @property bool $allow_manual_attendance
 * @property bool $is_active
 */
class AttendanceSetting extends Model
{
    use HasFactory;

    public const DEFAULT_PRESENT_TOLERANCE_MINUTES = 10;

    public const DEFAULT_LATE_TOLERANCE_MINUTES = 30;

    protected $fillable = [
        'schedule_id',
        'present_tolerance_minutes',
        'late_tolerance_minutes',
        'allow_manual_attendance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allow_manual_attendance' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
}
