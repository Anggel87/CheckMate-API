<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reported_by_user_id
 * @property int $schedule_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $severity
 * @property string|null $evidence
 * @property Carbon $incident_at
 * @property string $status
 * @property int $reviewed_by_user_id
 * @property string $type
 */
class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'reported_by_user_id',
        'schedule_id',
        'title',
        'description',
        'severity',
        'evidence',
        'incident_at',
        'status',
        'reviewed_by_user_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'incident_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'incident_students', 'incident_id', 'student_id')
            ->withPivot(['status', 'checked_at', 'notes', 'checked_by_user_id'])
            ->withTimestamps();
    }
}
