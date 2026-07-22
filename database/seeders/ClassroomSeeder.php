<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = [
            ['name' => 'Aula 101', 'building' => 'Edificio A'],
            ['name' => 'Aula 102', 'building' => 'Edificio A'],
            ['name' => 'Aula 201', 'building' => 'Edificio B'],
            ['name' => 'Aula 202', 'building' => 'Edificio B'],
            ['name' => 'Laboratorio de Computo 1', 'building' => 'Laboratorio'],
            ['name' => 'Laboratorio de Computo 2', 'building' => 'Laboratorio'],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::create($classroom);
        }
    }
}
