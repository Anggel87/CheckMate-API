<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $contrasena;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'verificado_en' => now(),
            'contrasena' => static::$contrasena ??= Hash::make('password'),
            'rol' => Role::Alumno,
            'activo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['rol' => Role::Admin]);
    }

    public function director(): static
    {
        return $this->state(['rol' => Role::Director]);
    }

    public function docente(): static
    {
        return $this->state(['rol' => Role::Docente]);
    }

    public function alumno(): static
    {
        return $this->state(['rol' => Role::Alumno]);
    }

    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }

    public function sinVerificar(): static
    {
        return $this->state(['verificado_en' => null]);
    }
}
