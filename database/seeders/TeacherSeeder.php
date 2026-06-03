<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teacherUser = User::factory()->teacher()->create([
            'first_name' => 'Carlos',
            'first_surname' => 'Ramírez',
            'second_surname' => 'López',
            'email' => 'teacher@checkmate.test',
            'password' => Hash::make('password'),
        ]);

        Teacher::create([
            'user_id' => $teacherUser->id,
            'speciality' => 'Tecnología',
            'is_active' => true,
        ]);

        Teacher::factory()->count(9)->create();
    }
}
