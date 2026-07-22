<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attendance_id
 * @property int $tutor_id
 * @property int $director_id
 * @property string $description
 * @property string|null $evidence
 * @property string $status
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
    ];

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
}
