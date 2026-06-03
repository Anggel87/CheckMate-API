<?php

namespace App\Models;

use Database\Factories\TutorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Legal or family tutor of a student (parent, guardian, etc.).
 * No login by default. Separate entity from AcademicTutor.
 *
 * @property int $id
 * @property string $first_name
 * @property string|null $second_name
 * @property string $first_surname
 * @property string $second_surname
 * @property string $phone
 * @property bool $is_active
 */
class Tutor extends Model
{
    /** @use HasFactory<TutorFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->first_surname} {$this->second_surname}");
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_tutor', 'tutor_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'receives_notifications'])
            ->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'tutors_id');
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class, 'tutor_id');
    }
}
