<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::firstWhere('status', 'ACTIVE');
        $groups = Group::all()->where('is_active', true);
        $subjects = Subject::all()->where('is_active', true);
        $teachers = Teacher::all()->where('is_active', true);
        $classrooms = Classroom::all();

        $days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];

        $timeSlots = [
            ['07:00:00', '08:00:00'],
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
            ['11:00:00', '12:00:00'],
        ];

        foreach ($groups as $group) {
            foreach ($days as $day) {
                $usedSlots = [];

                foreach ($subjects->random(3) as $subject) {
                    $availableSlots = array_filter(
                        $timeSlots,
                        fn ($slot) => ! in_array($slot[0], $usedSlots)
                    );

                    if (empty($availableSlots)) {
                        continue;
                    }

                    $slot = collect($availableSlots)->random();
                    $usedSlots[] = $slot[0];

                    Schedule::create([
                        'school_year_id' => $schoolYear->id,
                        'group_id' => $group->id,
                        'teacher_id' => $teachers->random()->id,
                        'subject_id' => $subject->id,
                        'classroom_id' => $classrooms->random()->id,
                        'day_of_week' => $day,
                        'start_time' => $slot[0],
                        'end_time' => $slot[1],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
