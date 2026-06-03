<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\Director;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $directors = Director::all();

        $careers = [
            [
                'name' => 'Tecnologías de la Información y Comunicación',
                'short_name' => 'TIC',
                'code' => 'TIC',
                'is_active' => true,
            ],
            [
                'name' => 'Administración de Empresas',
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
                'name' => 'Mecatrónica Industrial',
                'short_name' => 'MEC',
                'code' => 'MEC',
                'is_active' => true,
            ],
        ];

        foreach ($careers as $index => $career) {
            Career::create([
                ...$career,
                'directors_id' => $directors[$index % $directors->count()]->id,
            ]);
        }
    }
}
