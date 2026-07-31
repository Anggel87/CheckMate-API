<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mac_address' => strtoupper(fake()->unique()->regexify('([0-9A-F]{2}:){5}[0-9A-F]{2}')),
            'ip' => fake()->localIpv4(),
            'is_active' => true,
            'classroom_id' => Classroom::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
