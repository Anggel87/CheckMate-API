<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClaimSeeder extends Seeder
{
    public function run(): void
    {
        $director = User::whereHas('role', fn ($query) => $query->where('name', 'director_carrera'))->firstOrFail();

        $absences = Attendance::where('status', 'FALTA')->orderBy('id')->take(2)->get();

        foreach ($absences as $attendance) {
            Claim::factory()->create([
                'attendance_id' => $attendance->id,
                // claims.tutor_id guarda al alumno que reclama, no a un Tutor familiar
                // (mismo supuesto ya documentado en Módulo 7/8 de este proyecto).
                'tutor_id' => $attendance->student_id,
                'director_id' => $director->id,
            ]);
        }
    }
}
