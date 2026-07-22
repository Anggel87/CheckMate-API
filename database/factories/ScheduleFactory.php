<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 16);

        return [
            'school_year_id' => SchoolYear::factory(),
            'group_id' => Group::factory(),
            'teacher_id' => User::factory()->teacher(),
            'subject_id' => Subject::factory(),
            'classroom_id' => Classroom::factory(),
            'day_of_week' => fake()->randomElement(['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES']),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $startHour + 1),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
