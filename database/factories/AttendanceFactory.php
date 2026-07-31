<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'class_session_id' => ClassSession::factory(),
            'student_id' => User::factory()->student(),
            'schedule_id' => Schedule::factory(),
            'devices_id' => Device::factory(),
            'registered_at' => now(),
            'status' => 'PRESENTE',
            'method' => 'NFC',
        ];
    }

    public function present(): static
    {
        return $this->state(['status' => 'PRESENTE']);
    }

    public function late(): static
    {
        return $this->state(['status' => 'RETARDO']);
    }

    public function absent(): static
    {
        return $this->state(['status' => 'FALTA']);
    }

    public function justified(): static
    {
        return $this->state(['status' => 'JUSTIFICADA']);
    }
}
