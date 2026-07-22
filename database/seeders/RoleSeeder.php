<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['alumno', 'profesor', 'tutor_academico', 'administrador', 'director_carrera'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
