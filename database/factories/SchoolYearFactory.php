<?php

namespace Database\Factories;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolYear>
 */
class SchoolYearFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2020, 2035);

        return [
            'name' => "{$year}-".($year + 1),
            'start_date' => "{$year}-08-01",
            'end_date' => ($year + 1).'-06-30',
            'status' => 'UPCOMING',
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'ACTIVE']);
    }

    public function finished(): static
    {
        return $this->state(['status' => 'FINISHED']);
    }
}
