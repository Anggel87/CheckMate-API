<?php

namespace App\Models;

use Database\Factories\AcademicTutorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Academic tutor: teacher assigned to follow up one or more groups.
 *
 * @property int $id
 * @property int $teacher_id
 * @property bool $is_active
 */
class AcademicTutor extends Model
{
    /** @use HasFactory<AcademicTutorFactory> */
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_academic_tutor', 'academic_tutor_id', 'group_id')
            ->withPivot(['is_active', 'assigned_at']);
    }
}
