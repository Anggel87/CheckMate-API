<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reported_by_user_id
 * @property int $schedule_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $severity
 * @property string|null $evidence
 * @property Carbon|null $incident_at
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
    ];

    protected function casts(): array
    {
        return [
            'incident_at' => 'datetime',
        ];
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
}
