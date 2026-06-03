<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'group_id' => null,
            'student_number' => fake()->unique()->numerify('202#####'),
            'nfc_uid' => null,
            'qr_uuid' => null,
            'is_active' => true,
        ];
    }

    public function withNfc(): static
    {
        return $this->state([
            'nfc_uid' => strtoupper(fake()->unique()->lexify('????????')),
        ]);
    }

    public function withQr(): static
    {
        return $this->state([
            'qr_uuid' => Str::uuid()->toString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
