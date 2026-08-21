<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::firstWhere('status', 'ACTIVO');
        $groups = Group::all()->where('is_active', true);
        $subjects = Subject::all()->where('is_active', true);
        $teachers = User::where('active', true)
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->get();
        $classrooms = Classroom::all();

        $days = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES'];

        $timeSlots = [
            ['07:00:00', '08:00:00'],
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
            ['11:00:00', '12:00:00'],
        ];

        // Solo hay un profesor y un tutor academico sembrados (los @checkmate.com);
        // sin este control de ocupacion, asignarlos al azar por grupo los dejaria
        // dando dos clases distintas al mismo tiempo en su propio horario.
        $busyTeachers = [];
        $busyClassrooms = [];

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

                    $teacher = $this->pickFree($teachers, $busyTeachers, $day, $slot[0]);
                    $classroom = $this->pickFree($classrooms, $busyClassrooms, $day, $slot[0]);

                    $busyTeachers["{$teacher->id}|{$day}|{$slot[0]}"] = true;
                    $busyClassrooms["{$classroom->id}|{$day}|{$slot[0]}"] = true;

                    Schedule::create([
                        'school_year_id' => $schoolYear->id,
                        'group_id' => $group->id,
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'classroom_id' => $classroom->id,
                        'day_of_week' => $day,
                        'start_time' => $slot[0],
                        'end_time' => $slot[1],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Classroom|User>  $pool
     * @param  array<string, bool>  $busy
     * @return Classroom|User
     */
    private function pickFree(Collection $pool, array $busy, string $day, string $slotStart): Classroom|User
    {
        $free = $pool->reject(fn ($item) => isset($busy["{$item->id}|{$day}|{$slotStart}"]));

        return $free->isNotEmpty() ? $free->random() : $pool->random();
    }
}
