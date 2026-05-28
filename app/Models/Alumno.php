<?php

namespace App\Models;

use Database\Factories\AlumnoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $usuario_id
 * @property int|null $grupo_id
 * @property string $matricula
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $foto
 * @property string|null $telefono
 * @property string|null $direccion
 * @property Carbon|null $fecha_nacimiento
 * @property string|null $genero
 * @property string|null $nfc_uid
 * @property string|null $qr_uuid
 * @property bool $activo
 */
class Alumno extends Model
{
    /** @use HasFactory<AlumnoFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'grupo_id',
        'matricula',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'foto',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'genero',
        'nfc_uid',
        'qr_uuid',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tutores(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class, 'alumno_tutor')
            ->withPivot(['tipo_responsable', 'principal'])
            ->withTimestamps();
    }
}
