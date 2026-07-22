<?php

namespace App\Models;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $school_year_id
 * @property int $career_id
 * @property string $section
 * @property string $grade
 * @property string|null $shift
 * @property bool $is_active
 */
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = ['school_year_id', 'career_id', 'section', 'grade', 'shift', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class, 'career_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'group_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'group_id');
    }

    public function students(): HasMany
    {
        return $this->users()
            ->whereHas('role', fn ($query) => $query->where('name', 'alumno'));
    }

    public function academicTutors(): BelongsToMany
    {
        return $this->belongsToMany(AcademicTutor::class, 'group_academic_tutor', 'group_id', 'academic_tutor_id')
            ->withPivot(['is_active', 'assigned_at']);
    }
}
