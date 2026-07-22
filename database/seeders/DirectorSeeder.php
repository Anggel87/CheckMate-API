<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DirectorSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->careerDirector()->create([
            'first_name' => 'Roberto',
            'second_name' => null,
            'first_surname' => 'Hernandez',
            'second_surname' => 'Mora',
            'email' => 'director@checkmate.test',
            'password' => Hash::make('password'),
        ]);

        User::factory()->careerDirector()->count(2)->create();
    }
}
