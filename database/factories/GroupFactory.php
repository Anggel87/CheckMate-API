<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\Group;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'school_year_id' => SchoolYear::factory(),
            'career_id' => Career::factory(),
            'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'grade' => (string) fake()->numberBetween(1, 6),
            'shift' => fake()->randomElement(['MORNING', 'AFTERNOON', 'EVENING']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
