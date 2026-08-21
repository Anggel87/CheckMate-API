<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\User;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        // director_carrera@checkmate.com es el unico director sembrado; queda a cargo
        // de las 4 carreras hasta que el admin asigne directores reales desde el panel.
        $director = User::where('email', 'director_carrera@checkmate.com')->firstOrFail();

        $careers = [
            [
                'name' => 'Tecnologias de la Informacion y Comunicacion',
                'short_name' => 'TIC',
                'code' => 'TIC',
                'is_active' => true,
            ],
            [
                'name' => 'Administracion de Empresas',
                'short_name' => 'ADM',
                'code' => 'ADM',
                'is_active' => true,
            ],
            [
                'name' => 'Contabilidad y Finanzas',
                'short_name' => 'CONT',
                'code' => 'CONT',
                'is_active' => true,
            ],
            [
                'name' => 'Mecatronica Industrial',
                'short_name' => 'MEC',
                'code' => 'MEC',
                'is_active' => true,
            ],
        ];

        foreach ($careers as $career) {
            Career::create([
                ...$career,
                'director_id' => $director->id,
            ]);
        }
    }
}
