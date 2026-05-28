<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\TutorAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorAcademico>
 */
class TutorAcademicoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'docente_id' => Docente::factory(),
            'activo' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }
}
