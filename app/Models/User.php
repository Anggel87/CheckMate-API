<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $nombre
 * @property string $correo
 * @property string|null $verificado_en
 * @property string $contrasena
 * @property Role $rol
 * @property bool $activo
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'correo',
        'contrasena',
        'rol',
        'activo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'contrasena',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verificado_en' => 'datetime',
            'contrasena' => 'hashed',
            'rol' => Role::class,
            'activo' => 'boolean',
        ];
    }

    // ─── Overrides de autenticación ──────────────────────────────────────────

    /**
     * Nombre de la columna de correo para el sistema de auth.
     */
    public function getAuthIdentifierName(): string
    {
        return 'correo';
    }

    /**
     * Valor hasheado de la contraseña para validación.
     */
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    /**
     * Nombre de la columna de contraseña para Auth::attempt().
     */
    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->activo;
    }

    public function hasRole(Role $rol): bool
    {
        return $this->rol === $rol;
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function alumno(): HasOne
    {
        return $this->hasOne(Alumno::class, 'usuario_id');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'usuario_id');
    }

    public function director(): HasOne
    {
        return $this->hasOne(Director::class, 'usuario_id');
    }

    public function tutorAcademico(): HasOne
    {
        return $this->hasOne(TutorAcademico::class, 'usuario_id');
    }
}
