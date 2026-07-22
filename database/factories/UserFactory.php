<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'role_id' => Role::firstOrCreate(['name' => 'alumno'])->id,
            'first_name' => fake()->firstName(),
            'second_name' => fake()->optional(0.4)->firstName(),
            'first_surname' => fake()->lastName(),
            'second_surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'verified_at' => now(),
            'active' => true,
            'photo' => 'users/default.png',
            'phone' => fake()->numerify('##########'),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['M', 'F', 'OTRO']),
        ];
    }

    public function director(): static
    {
        return $this->careerDirector();
    }

    public function careerDirector(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(['name' => 'director_carrera'])->id,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(['name' => 'profesor'])->id,
        ]);
    }

    public function academicTutor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(['name' => 'tutor_academico'])->id,
        ]);
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(['name' => 'administrador'])->id,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(['name' => 'alumno'])->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['verified_at' => null]);
    }
}
