<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Justification>
 */
class JustificationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory()->absent(),
            'justified_by_user_id' => User::factory()->student(),
            'reason' => fake()->sentence(),
            'file' => null,
            'justified_at' => now(),
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
