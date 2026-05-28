<?php

namespace App\Models;

use Database\Factories\DocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $telefono
 * @property string|null $foto
 * @property string|null $especialidad
 * @property bool $activo
 */
class Docente extends Model
{
    /** @use HasFactory<DocenteFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'foto',
        'especialidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
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

    public function tutorAcademico(): HasOne
    {
        return $this->hasOne(TutorAcademico::class);
    }
}
