<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = Schedule::query()
            ->where('is_active', true)
            ->with('group.students')
            ->get()
            ->unique('group_id')
            ->filter(fn (Schedule $schedule) => $schedule->group->students->isNotEmpty())
            ->take(2);

        foreach ($schedules as $schedule) {
            $incident = Incident::factory()->create([
                'reported_by_user_id' => $schedule->teacher_id,
                'reviewed_by_user_id' => $schedule->teacher_id,
                'schedule_id' => $schedule->id,
            ]);

            $students = $schedule->group->students->take(3);
            $statuses = ['PRESENTE', 'AUSENTE', 'SEGURO'];

            foreach ($students as $index => $student) {
                $incident->students()->attach($student->id, [
                    'status' => $statuses[$index] ?? 'DESCONOCIDO',
                    'checked_by_user_id' => $schedule->teacher_id,
                    'checked_at' => now(),
                    'notes' => null,
                ]);
            }
        }
    }
}
