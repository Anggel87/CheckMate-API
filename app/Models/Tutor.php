<?php

namespace App\Models;

use Database\Factories\TutorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tutor legal o familiar del alumno (padre, madre, tutor externo).
 * No tiene login por defecto. Entidad separada de TutorAcademico.
 *
 * @property int $id
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $telefono
 * @property string|null $correo
 * @property string|null $direccion
 * @property string|null $parentesco
 * @property bool $recibe_notificaciones
 * @property bool $activo
 */
class Tutor extends Model
{
    /** @use HasFactory<TutorFactory> */
    use HasFactory;

    protected $table = 'tutores';

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'correo',
        'direccion',
        'parentesco',
        'recibe_notificaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'recibe_notificaciones' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'alumno_tutor')
            ->withPivot(['tipo_responsable', 'principal'])
            ->withTimestamps();
    }
}
