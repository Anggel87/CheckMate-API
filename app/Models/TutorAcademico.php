<?php

namespace App\Models;

use Database\Factories\TutorAcademicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tutor académico: docente asignado al seguimiento de uno o varios grupos.
 * Siempre tiene un perfil en docentes. Su información de nombre y contacto
 * vive en la tabla docentes, no aquí.
 *
 * @property int $id
 * @property int $docente_id
 * @property bool $activo
 */
class TutorAcademico extends Model
{
    /** @use HasFactory<TutorAcademicoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'tutores_academicos';

    protected $fillable = [
        'docente_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * Grupos asignados a este tutor académico.
     * La relación completa se define al crear la migración grupo_tutor_academico.
     */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'grupo_tutor_academico', 'id_tutor_academico', 'id_grupo')
            ->withPivot(['id_ciclo_escolar', 'activo', 'fecha_asignacion'])
            ->withTimestamps();
    }
}
