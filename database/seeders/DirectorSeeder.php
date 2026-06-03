<?php

namespace Database\Seeders;

use App\Models\Director;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DirectorSeeder extends Seeder
{
    public function run(): void
    {
        $directorUser = User::factory()->director()->create([
            'first_name' => 'Roberto',
            'second_name' => null,
            'first_surname' => 'Hernández',
            'second_surname' => 'Mora',
            'email' => 'director@checkmate.test',
            'password' => Hash::make('password'),
        ]);

        Director::create([
            'id' => $directorUser->id,
            'user_id' => $directorUser->id,
            'is_active' => true,
        ]);

        Director::factory()->count(2)->create();
    }
}
