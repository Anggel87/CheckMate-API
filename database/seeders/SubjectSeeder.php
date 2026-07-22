<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matematicas', 'code' => 'MAT-1', 'description' => 'Algebra, geometria y calculo basico.'],
            ['name' => 'Espanol', 'code' => 'ESP-1', 'description' => 'Comprension lectora y redaccion.'],
            ['name' => 'Historia', 'code' => 'HIS-1', 'description' => 'Historia de Mexico y del mundo.'],
            ['name' => 'Ingles', 'code' => 'ING-1', 'description' => 'Ingles comunicativo nivel basico-intermedio.'],
            ['name' => 'Fisica', 'code' => 'FIS-1', 'description' => 'Mecanica clasica y termodinamica.'],
            ['name' => 'Quimica', 'code' => 'QUI-1', 'description' => 'Quimica general e inorganica.'],
            ['name' => 'Biologia', 'code' => 'BIO-1', 'description' => 'Celula, genetica y ecologia.'],
            ['name' => 'Geografia', 'code' => 'GEO-1', 'description' => 'Geografia fisica y humana.'],
            ['name' => 'Educacion Fisica', 'code' => 'EDF-1', 'description' => null],
            ['name' => 'Tecnologia', 'code' => 'TEC-1', 'description' => 'Fundamentos de tecnologia e informatica.'],
        ];

        foreach ($subjects as $subject) {
            Subject::create([...$subject, 'is_active' => true]);
        }
    }
}
