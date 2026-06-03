<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $role_id
 * @property string $first_name
 * @property string|null $second_name
 * @property string $first_surname
 * @property string $second_surname
 * @property string $email
 * @property string $password
 * @property string|null $verified_at
 * @property bool $active
 * @property string|null $photo
 * @property string|null $phone
 * @property Carbon|null $birth_date
 * @property string|null $gender
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'role_id',
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'email',
        'password',
        'active',
        'photo',
        'phone',
        'birth_date',
        'gender',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->first_surname} {$this->second_surname}");
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    public function director(): HasOne
    {
        return $this->hasOne(Director::class, 'user_id');
    }

    public function academicTutor(): HasOneThrough
    {
        return $this->hasOneThrough(AcademicTutor::class, Teacher::class, 'user_id', 'teacher_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'users_id');
    }
}
