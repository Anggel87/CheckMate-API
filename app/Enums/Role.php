<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'ADMIN';
    case Director = 'DIRECTOR';
    case Docente = 'DOCENTE';
    case Alumno = 'ALUMNO';

    public function label(): string
    {
        return match ($this) {
            Role::Admin => 'Administrador',
            Role::Director => 'Director',
            Role::Docente => 'Docente',
            Role::Alumno => 'Alumno',
        };
    }
}
