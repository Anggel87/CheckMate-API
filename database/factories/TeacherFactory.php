<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->teacher(),
            'speciality' => fake()->optional(0.6)->randomElement([
                'Matemáticas', 'Español', 'Ciencias', 'Historia', 'Inglés',
                'Educación Física', 'Arte', 'Geografía', 'Tecnología',
            ]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
