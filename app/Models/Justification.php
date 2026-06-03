<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attendance_id
 * @property int $justified_by_user_id
 * @property string $reason
 * @property string|null $file
 * @property Carbon $justified_at
 * @property string $status
 */
class Justification extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'justified_by_user_id',
        'reason',
        'file',
        'justified_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'justified_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function justifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'justified_by_user_id');
    }
}
