<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'tutor_id' => User::factory()->student(),
            'director_id' => User::factory()->careerDirector(),
            'description' => fake()->sentence(10),
            'evidence' => null,
            'status' => 'PENDIENTE',
        ];
    }

    public function accepted(): static
    {
        return $this->state(['status' => 'ACEPTADO']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'RECHAZADO']);
    }
}
