<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->docente(),
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->optional(0.8)->lastName(),
            'telefono' => fake()->optional(0.7)->numerify('6##-###-####'),
            'foto' => null,
            'especialidad' => fake()->optional(0.6)->randomElement([
                'Matemáticas', 'Español', 'Ciencias', 'Historia', 'Inglés',
                'Educación Física', 'Artes', 'Geografía', 'Tecnología',
            ]),
            'activo' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }
}
