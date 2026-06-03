<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->bothify('Aula-##'),
            'building' => fake()->randomElement(['Edificio A', 'Edificio B', 'Edificio C', 'Laboratorio']),
        ];
    }
}
