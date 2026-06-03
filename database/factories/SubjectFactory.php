<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    private static array $subjects = [
        'Matemáticas', 'Español', 'Historia', 'Ciencias Naturales',
        'Inglés', 'Geografía', 'Física', 'Química', 'Biología',
        'Educación Física', 'Arte', 'Tecnología',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::$subjects);

        return [
            'name' => $name,
            'code' => strtoupper(mb_substr($name, 0, 3)).'-'.fake()->numberBetween(1, 9),
            'description' => fake()->optional(0.6)->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
