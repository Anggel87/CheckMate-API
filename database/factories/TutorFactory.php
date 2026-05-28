<?php

namespace Database\Factories;

use App\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tutor>
 */
class TutorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->optional(0.8)->lastName(),
            'telefono' => fake()->optional(0.9)->numerify('6##-###-####'),
            'correo' => fake()->optional(0.7)->safeEmail(),
            'direccion' => fake()->optional(0.6)->address(),
            'parentesco' => fake()->randomElement(['PADRE', 'MADRE', 'TUTOR', 'OTRO']),
            'recibe_notificaciones' => true,
            'activo' => true,
        ];
    }

    public function sinNotificaciones(): static
    {
        return $this->state(['recibe_notificaciones' => false]);
    }

    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }
}
