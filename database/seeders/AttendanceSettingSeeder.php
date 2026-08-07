<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class AttendanceSettingSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = Schedule::where('is_active', true)->orderBy('id')->take(2)->get();

        $tolerances = [
            ['present_tolerance_minutes' => 5, 'late_tolerance_minutes' => 15, 'allow_manual_attendance' => true],
            ['present_tolerance_minutes' => 15, 'late_tolerance_minutes' => 45, 'allow_manual_attendance' => false],
        ];

        foreach ($schedules as $index => $schedule) {
            AttendanceSetting::firstOrCreate(
                ['schedule_id' => $schedule->id],
                [...$tolerances[$index], 'is_active' => true]
            );
        }
    }
}
