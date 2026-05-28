<?php

namespace Database\Factories;

use App\Models\Director;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Director>
 */
class DirectorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->director(),
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->optional(0.8)->lastName(),
            'telefono' => fake()->optional(0.7)->numerify('6##-###-####'),
            'foto' => null,
            'activo' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }
}
