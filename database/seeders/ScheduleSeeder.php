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

        // Solo hay un profesor y un tutor academico sembrados (los @checkmate.com); el estado de
        // ocupacion se comparte entre TODOS los grupos de un dia (no por grupo) para que dos
        // grupos jamas terminen compartiendo profesor o aula a la misma hora. A diferencia de la
        // version anterior, aqui NUNCA se cae a una asignacion al azar cuando no hay nadie libre:
        // si no hay profesor/aula disponible para una materia, esa clase simplemente se omite en
        // vez de generar un horario empalmado.
        foreach ($days as $day) {
            $busyTeachers = [];
            $busyClassrooms = [];

            foreach ($groups as $group) {
                $usedSlots = [];

                foreach ($subjects->random(3) as $subject) {
                    $availableSlots = collect($timeSlots)->reject(
                        fn ($slot) => in_array($slot[0], $usedSlots)
                    );

                    $slot = null;
                    $teacher = null;
                    $classroom = null;

                    foreach ($availableSlots->shuffle() as $candidateSlot) {
                        $freeTeacher = $this->pickFree($teachers, $busyTeachers, $candidateSlot[0]);
                        $freeClassroom = $this->pickFree($classrooms, $busyClassrooms, $candidateSlot[0]);

                        if ($freeTeacher !== null && $freeClassroom !== null) {
                            $slot = $candidateSlot;
                            $teacher = $freeTeacher;
                            $classroom = $freeClassroom;
                            break;
                        }
                    }

                    if ($slot === null) {
                        continue;
                    }

                    $usedSlots[] = $slot[0];
                    $busyTeachers["{$teacher->id}|{$slot[0]}"] = true;
                    $busyClassrooms["{$classroom->id}|{$slot[0]}"] = true;

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
     */
    private function pickFree(Collection $pool, array $busy, string $slotStart): Classroom|User|null
    {
        $free = $pool->reject(fn ($item) => isset($busy["{$item->id}|{$slotStart}"]));

        return $free->isNotEmpty() ? $free->random() : null;
    }
}
