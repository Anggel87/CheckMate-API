<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openedAt = fake()->dateTimeBetween('-1 month');

        return [
            'schedule_id' => Schedule::factory(),
            'teacher_id' => User::factory()->teacher(),
            'device_id' => Device::factory(),
            'opened_at' => $openedAt,
            'closed_at' => null,
            'status' => 'ABIERTA',
            'opening_method' => fake()->randomElement(['NFC', 'QR', 'MANUAL']),
            'is_active' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state([
            'closed_at' => now(),
            'status' => 'CERRADA',
            'is_active' => false,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'CANCELADA',
            'is_active' => false,
        ]);
    }
}
