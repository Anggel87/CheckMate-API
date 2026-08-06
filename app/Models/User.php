<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $role_id
 * @property int|null $governance_user_id
 * @property int|null $group_id
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
        'governance_user_id',
        'group_id',
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

    /**
     * Permisos efectivos: los de los permission_groups del rol, mas los
     * overrides individuales (PERMITIR agrega, DENEGAR quita).
     *
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        $rolePermissions = $this->role
            ->permissionGroups()
            ->with('permissions')
            ->get()
            ->flatMap(fn (PermissionGroup $group) => $group->permissions)
            ->filter(fn (Permission $permission) => $permission->is_active)
            ->pluck('key_name');

        $overrides = $this->permissionOverrides()->with('permission')->get();

        $allowed = $rolePermissions->merge(
            $overrides->where('type', 'PERMITIR')->pluck('permission.key_name')
        );

        $denied = $overrides->where('type', 'DENEGAR')->pluck('permission.key_name');

        return $allowed->diff($denied)->unique()->values()->all();
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function details(): HasOne
    {
        return $this->hasOne(UserDetail::class, 'user_id');
    }

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class, 'student_tutor', 'student_id', 'tutor_id')
            ->withPivot(['relationship', 'is_primary', 'receives_notifications'])
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'tutor_id');
    }

    public function justifications(): HasMany
    {
        return $this->hasMany(Justification::class, 'justified_by_user_id');
    }

    public function incidentStudents(): HasMany
    {
        return $this->hasMany(IncidentStudent::class, 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class, 'user_id');
    }

    public function studentNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class, 'student_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'teacher_id');
    }

    public function academicTutor(): HasOne
    {
        return $this->hasOne(AcademicTutor::class, 'user_id');
    }

    public function managedCareers(): HasMany
    {
        return $this->hasMany(Career::class, 'director_id');
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by_user_id');
    }

    public function reviewedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reviewed_by_user_id');
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class, 'users_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'users_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('role', fn (Builder $roleQuery) => $roleQuery->where('name', $role));
    }
}
