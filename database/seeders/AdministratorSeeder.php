<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->administrator()->create([
            'first_name' => 'Ana',
            'second_name' => 'Maria',
            'first_surname' => 'Garcia',
            'second_surname' => 'Flores',
            'email' => 'administrador@checkmate.test',
            'password' => Hash::make('password'),
        ]);
    }
}
