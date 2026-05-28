<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumno>
 */
class AlumnoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->alumno(),
            'grupo_id' => null,
            'matricula' => fake()->unique()->numerify('202#####'),
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->optional(0.8)->lastName(),
            'foto' => null,
            'telefono' => fake()->optional(0.5)->numerify('6##-###-####'),
            'direccion' => fake()->optional(0.5)->address(),
            'fecha_nacimiento' => fake()->optional(0.9)->dateTimeBetween('-20 years', '-10 years')?->format('Y-m-d'),
            'genero' => fake()->optional(0.9)->randomElement(['MASCULINO', 'FEMENINO', 'OTRO']),
            'nfc_uid' => null,
            'qr_uuid' => null,
            'activo' => true,
        ];
    }

    public function conNfc(): static
    {
        return $this->state([
            'nfc_uid' => strtoupper(fake()->unique()->hexColor()),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }

    public function sinCuenta(): static
    {
        return $this->state(['user_id' => null]);
    }
}
