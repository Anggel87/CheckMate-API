<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attendance_id
 * @property int $tutor_id
 * @property int $director_id
 * @property string $description
 * @property string|null $evidence
 * @property string $status
 * @property int|null $action_by_user_id
 * @property Carbon|null $action_at
 * @property string|null $comment
 */
class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'tutor_id',
        'director_id',
        'description',
        'evidence',
        'status',
        'action_by_user_id',
        'action_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'action_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }
}
