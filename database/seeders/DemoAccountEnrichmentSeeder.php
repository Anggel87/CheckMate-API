<?php

namespace Database\Seeders;

use App\Models\AcademicTutor;
use App\Models\Attendance;
use App\Models\Career;
use App\Models\ClassSession;
use App\Models\Claim;
use App\Models\Device;
use App\Models\Group;
use App\Models\Incident;
use App\Models\Justification;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoAccountEnrichmentSeeder extends Seeder
{
    private const DAY_CONSTANTS = [
        'LUNES' => Carbon::MONDAY,
        'MARTES' => Carbon::TUESDAY,
        'MIERCOLES' => Carbon::WEDNESDAY,
        'JUEVES' => Carbon::THURSDAY,
        'VIERNES' => Carbon::FRIDAY,
        'SABADO' => Carbon::SATURDAY,
        'DOMINGO' => Carbon::SUNDAY,
    ];

    /**
     * Los usuarios @checkmate.com de SimpleUserSeeder quedan vinculados a gobernanza
     * pero sin ningun dato real asociado (sin grupo, sin horarios, sin carrera a
     * cargo). Este seeder los conecta con la estructura academica ya sembrada para
     * que el login de prueba muestre asistencias, clases, reclamos y justificantes
     * reales en vez de pantallas vacias.
     */
    public function run(): void
    {
        $alumno = User::where('email', 'alumno@checkmate.com')->first();
        $profesor = User::where('email', 'profesor@checkmate.com')->first();
        $tutorAcademico = User::where('email', 'tutor_academico@checkmate.com')->first();
        $director = User::where('email', 'director_carrera@checkmate.com')->first();

        if (! $alumno || ! $profesor || ! $tutorAcademico || ! $director) {
            return;
        }

        $group = Group::find(2);

        if (! $group) {
            return;
        }

        $this->linkDirectorToCareer($director, $group->career_id);
        $this->linkStudentToGroup($alumno, $group->id);
        $this->linkAcademicTutorToGroup($tutorAcademico, $group->id);

        $scheduleIds = [2, 8, 14, 20, 26];
        Schedule::whereIn('id', $scheduleIds)->update(['teacher_id' => $profesor->id]);

        $statusesByScheduleId = [
            2 => 'PRESENTE',
            8 => 'PRESENTE',
            14 => 'RETARDO',
            20 => 'FALTA',
            26 => 'FALTA',
        ];

        $students = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->where('group_id', $group->id)
            ->get();

        $absencesForAlumno = [];

        foreach (Schedule::whereIn('id', $scheduleIds)->get() as $schedule) {
            $classSession = $this->classSessionFor($schedule, $profesor);
            $device = $classSession->device;

            foreach ($students as $student) {
                $status = $student->id === $alumno->id
                    ? $statusesByScheduleId[$schedule->id]
                    : fake()->randomElement(['PRESENTE', 'PRESENTE', 'PRESENTE', 'RETARDO', 'FALTA']);

                $attendance = Attendance::firstOrCreate(
                    ['class_session_id' => $classSession->id, 'student_id' => $student->id],
                    [
                        'schedule_id' => $schedule->id,
                        'devices_id' => $device->id,
                        'registered_at' => $status === 'FALTA'
                            ? $classSession->closed_at
                            : $classSession->opened_at->copy()->addMinutes(fake()->numberBetween(0, 15)),
                        'status' => $status,
                        'method' => $status === 'FALTA' ? 'SISTEMA' : 'NFC',
                    ],
                );

                if ($student->id === $alumno->id && $status === 'FALTA') {
                    $absencesForAlumno[] = $attendance;
                }
            }
        }

        $this->createJustificationAndClaim($alumno, $director, $absencesForAlumno);
        $this->createIncident($profesor, $scheduleIds, $students, $alumno);
    }

    private function linkDirectorToCareer(User $director, int $careerId): void
    {
        Career::where('id', $careerId)->update(['director_id' => $director->id]);
    }

    private function linkStudentToGroup(User $alumno, int $groupId): void
    {
        $alumno->update(['group_id' => $groupId]);
    }

    private function linkAcademicTutorToGroup(User $tutorAcademico, int $groupId): void
    {
        $academicTutor = AcademicTutor::firstOrCreate(
            ['user_id' => $tutorAcademico->id],
            ['is_active' => true],
        );

        DB::table('group_academic_tutor')->updateOrInsert(
            ['group_id' => $groupId, 'academic_tutor_id' => $academicTutor->id],
            ['is_active' => true, 'assigned_at' => now()->toDateString()],
        );
    }

    private function classSessionFor(Schedule $schedule, User $profesor): ClassSession
    {
        $date = $this->lastOccurrenceOf($schedule->day_of_week);

        $device = Device::firstOrCreate(
            ['classroom_id' => $schedule->classroom_id],
            [
                'mac_address' => strtoupper(fake()->unique()->regexify('([0-9A-F]{2}:){5}[0-9A-F]{2}')),
                'ip' => fake()->localIpv4(),
                'is_active' => true,
            ],
        );

        $openedAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->start_time);
        $closedAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->end_time);

        return ClassSession::firstOrCreate(
            ['schedule_id' => $schedule->id, 'date' => $date->format('Y-m-d')],
            [
                'teacher_id' => $profesor->id,
                'device_id' => $device->id,
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'status' => 'CERRADA',
                'opening_method' => 'NFC',
                'is_active' => false,
            ],
        );
    }

    private function lastOccurrenceOf(string $dayOfWeek): Carbon
    {
        return Carbon::now()->previous(self::DAY_CONSTANTS[$dayOfWeek] ?? Carbon::MONDAY);
    }

    /**
     * @param  array<int, Attendance>  $absencesForAlumno
     */
    private function createJustificationAndClaim(User $alumno, User $director, array $absencesForAlumno): void
    {
        if (! isset($absencesForAlumno[0])) {
            return;
        }

        Justification::firstOrCreate(
            ['attendance_id' => $absencesForAlumno[0]->id],
            [
                'justified_by_user_id' => $alumno->id,
                'reason' => 'Cita medica programada, se anexa comprobante.',
                'file' => null,
                'justified_at' => now(),
                'status' => 'PENDIENTE',
            ],
        );

        if (! isset($absencesForAlumno[1])) {
            return;
        }

        Claim::firstOrCreate(
            ['attendance_id' => $absencesForAlumno[1]->id],
            [
                // claims.tutor_id guarda al alumno que reclama, no a un Tutor familiar.
                'tutor_id' => $alumno->id,
                'director_id' => $director->id,
                'description' => 'La falta fue registrada por error, el alumno si asistio a clase.',
                'evidence' => null,
                'status' => 'PENDIENTE',
            ],
        );
    }

    /**
     * @param  array<int, int>  $scheduleIds
     * @param  \Illuminate\Support\Collection<int, User>  $students
     */
    private function createIncident(User $profesor, array $scheduleIds, $students, User $alumno): void
    {
        $scheduleId = $scheduleIds[array_key_last($scheduleIds)];

        if (Incident::where('schedule_id', $scheduleId)->where('reported_by_user_id', $profesor->id)->exists()) {
            return;
        }

        $incident = Incident::factory()->create([
            'reported_by_user_id' => $profesor->id,
            'reviewed_by_user_id' => $profesor->id,
            'schedule_id' => $scheduleId,
            'title' => 'Simulacro de evacuacion',
            'status' => 'RESUELTO',
            'type' => 'OTHER',
        ]);

        $roster = $students->take(3);

        if (! $roster->contains('id', $alumno->id)) {
            $roster->push($alumno);
        }

        $statuses = ['SEGURO', 'SEGURO', 'PRESENTE'];

        foreach ($roster->values() as $index => $student) {
            $incident->students()->attach($student->id, [
                'status' => $statuses[$index] ?? 'SEGURO',
                'checked_by_user_id' => $profesor->id,
                'checked_at' => now(),
                'notes' => null,
            ]);
        }
    }
}
