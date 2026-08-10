<?php

namespace Database\Seeders;

use App\Models\AcademicTutor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimpleUserSeeder extends Seeder
{
    /**
     * Un usuario de prueba por rol, con credenciales simples (@checkmate.com / password123)
     * para demos y para vincular con gobernanza via `governance:link-users`.
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        User::factory()->student()->create([
            'first_name' => 'Alumno',
            'first_surname' => 'Demo',
            'second_surname' => 'Checkmate',
            'email' => 'alumno@checkmate.com',
            'password' => $password,
        ]);

        User::factory()->teacher()->create([
            'first_name' => 'Profesor',
            'first_surname' => 'Demo',
            'second_surname' => 'Checkmate',
            'email' => 'profesor@checkmate.com',
            'password' => $password,
        ]);

        $academicTutorUser = User::factory()->academicTutor()->create([
            'first_name' => 'Tutor',
            'second_name' => 'Academico',
            'first_surname' => 'Demo',
            'second_surname' => 'Checkmate',
            'email' => 'tutor_academico@checkmate.com',
            'password' => $password,
        ]);

        AcademicTutor::create([
            'user_id' => $academicTutorUser->id,
            'is_active' => true,
        ]);

        User::factory()->administrator()->create([
            'first_name' => 'Administrador',
            'first_surname' => 'Demo',
            'second_surname' => 'Checkmate',
            'email' => 'administrador@checkmate.com',
            'password' => $password,
        ]);

        User::factory()->careerDirector()->create([
            'first_name' => 'Director',
            'first_surname' => 'Demo',
            'second_surname' => 'Checkmate',
            'email' => 'director_carrera@checkmate.com',
            'password' => $password,
        ]);
    }
}
