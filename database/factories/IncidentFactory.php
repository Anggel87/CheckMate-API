<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'reported_by_user_id' => User::factory()->teacher(),
            'schedule_id' => Schedule::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(10),
            'severity' => fake()->randomElement(['BAJA', 'MEDIA', 'ALTA', 'CRITICA']),
            'evidence' => null,
            'incident_at' => now(),
            'status' => 'ACTIVO',
            'reviewed_by_user_id' => User::factory()->teacher(),
            'type' => fake()->randomElement(['FIRE', 'GAS', 'EARTHQUAKE', 'OTHER']),
        ];
    }

    public function resolved(): static
    {
        return $this->state(['status' => 'RESUELTO']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'CANCELADO']);
    }
}
