<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->teacher()->create([
            'first_name' => 'Carlos',
            'first_surname' => 'Ramirez',
            'second_surname' => 'Lopez',
            'email' => 'teacher@checkmate.test',
            'password' => Hash::make('password'),
        ]);

        User::factory()->teacher()->count(9)->create();
    }
}
