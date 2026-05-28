<?php

namespace App\Models;

use Database\Factories\DirectorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $telefono
 * @property string|null $foto
 * @property bool $activo
 */
class Director extends Model
{
    /** @use HasFactory<DirectorFactory> */
    use HasFactory;

    protected $table = 'directores';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'foto',
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
}
