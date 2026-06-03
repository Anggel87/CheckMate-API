<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matemáticas', 'code' => 'MAT-1', 'description' => 'Álgebra, geometría y cálculo básico.'],
            ['name' => 'Español', 'code' => 'ESP-1', 'description' => 'Comprensión lectora y redacción.'],
            ['name' => 'Historia', 'code' => 'HIS-1', 'description' => 'Historia de México y del mundo.'],
            ['name' => 'Inglés', 'code' => 'ING-1', 'description' => 'Inglés comunicativo nivel básico-intermedio.'],
            ['name' => 'Física', 'code' => 'FIS-1', 'description' => 'Mecánica clásica y termodinámica.'],
            ['name' => 'Química', 'code' => 'QUI-1', 'description' => 'Química general e inorgánica.'],
            ['name' => 'Biología', 'code' => 'BIO-1', 'description' => 'Célula, genética y ecología.'],
            ['name' => 'Geografía', 'code' => 'GEO-1', 'description' => 'Geografía física y humana.'],
            ['name' => 'Educación Física', 'code' => 'EDF-1', 'description' => null],
            ['name' => 'Tecnología', 'code' => 'TEC-1', 'description' => 'Fundamentos de tecnología e informática.'],
        ];

        foreach ($subjects as $subject) {
            Subject::create([...$subject, 'is_active' => true]);
        }
    }
}
