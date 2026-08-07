<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Justification;
use Illuminate\Database\Seeder;

class JustificationSeeder extends Seeder
{
    public function run(): void
    {
        $absences = Attendance::where('status', 'FALTA')->orderBy('id')->skip(2)->take(2)->get();

        foreach ($absences as $attendance) {
            Justification::factory()->create([
                'attendance_id' => $attendance->id,
                'justified_by_user_id' => $attendance->student_id,
            ]);
        }
    }
}
