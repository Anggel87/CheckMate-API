<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'alumno',
        ];
    }

    public function admin(): static
    {
        return $this->state(['name' => 'administrador']);
    }

    public function director(): static
    {
        return $this->state(['name' => 'director_carrera']);
    }

    public function teacher(): static
    {
        return $this->state(['name' => 'profesor']);
    }

    public function student(): static
    {
        return $this->state(['name' => 'alumno']);
    }
}
