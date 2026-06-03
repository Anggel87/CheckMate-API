<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attendance_id
 * @property int $reviewed_by_user_id
 * @property string $description
 * @property string|null $evidence
 * @property string $status
 */
class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'reviewed_by_user_id',
        'description',
        'evidence',
        'status',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
