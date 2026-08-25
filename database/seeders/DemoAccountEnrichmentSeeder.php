<?php

namespace Database\Seeders;

use App\Models\AcademicTutor;
use App\Models\Attendance;
use App\Models\Career;
use App\Models\Claim;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Group;
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

        $group = Group::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $group) {
            return;
        }

        $this->linkDirectorToCareer($director, $group->career_id);
        $this->linkStudentToGroup($alumno, $group->id);
        $this->linkAcademicTutorToGroup($tutorAcademico, $group->id);

        // Ancla estas clases al aula donde ya vive el dispositivo NFC de demo
        // (ver DeviceSeeder) para que el flujo fisico de check-in siempre tenga
        // un horario real de profesor@checkmate.com que abrir.
        $demoClassroom = Classroom::where('name', 'Aula 101')->first() ?? Classroom::first();

        // Reasignar sin cuidado empalmaria a profesor@checkmate.com (choca con otro horario
        // suyo, en otro grupo, a la misma hora) o al aula 101 (choca con otro grupo que ya
        // usa esa aula a esa hora). Solo se reasignan los horarios del grupo ancla que caen
        // en un dia/hora donde ambos ya estan libres.
        $busyTeacherSlots = Schedule::where('teacher_id', $profesor->id)
            ->where('is_active', true)
            ->get()
            ->map(fn (Schedule $schedule) => "{$schedule->day_of_week}|{$schedule->start_time}")
            ->flip();

        $busyClassroomSlots = Schedule::where('classroom_id', $demoClassroom->id)
            ->where('is_active', true)
            ->get()
            ->map(fn (Schedule $schedule) => "{$schedule->day_of_week}|{$schedule->start_time}")
            ->flip();

        $scheduleIds = Schedule::query()
            ->where('group_id', $group->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(function (Schedule $schedule) use ($busyTeacherSlots, $busyClassroomSlots, $profesor, $demoClassroom) {
                $key = "{$schedule->day_of_week}|{$schedule->start_time}";

                // Cada choque se evalua por separado: un horario que ya es de profesor@checkmate.com
                // (o que ya vive en el aula 101) no puede chocar consigo mismo en ese lado, pero SI
                // debe seguir revisando el otro lado, porque ambos campos se sobreescriben juntos.
                $teacherConflict = $schedule->teacher_id !== $profesor->id && $busyTeacherSlots->has($key);
                $classroomConflict = $schedule->classroom_id !== $demoClassroom->id && $busyClassroomSlots->has($key);

                return ! $teacherConflict && ! $classroomConflict;
            })
            ->take(5)
            ->pluck('id')
            ->all();

        Schedule::whereIn('id', $scheduleIds)->update([
            'teacher_id' => $profesor->id,
            'classroom_id' => $demoClassroom->id,
        ]);

        $statusesByScheduleId = collect($scheduleIds)
            ->mapWithKeys(fn (int $scheduleId, int $index) => [
                $scheduleId => match ($index) {
                    0, 1 => 'PRESENTE',
                    2 => 'RETARDO',
                    default => 'FALTA',
                },
            ])
            ->all();

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
        $this->seedTutorAcademicoAttendance($tutorAcademico);
    }

    /**
     * tutor_academico@checkmate.com ya tiene sus propios horarios activos (heredados del
     * seed base, repartidos en mas de un grupo), pero sin asistencias reales: el portal de
     * tutor academico muestra los grupos de esos horarios (via teacher_id), no el grupo de
     * "alumno@checkmate.com" que este seeder vincula arriba. Sin esto, el calendario de
     * asistencia siempre aparece vacio al probar con la cuenta de tutor academico.
     */
    private function seedTutorAcademicoAttendance(User $tutorAcademico): void
    {
        $schedules = Schedule::where('teacher_id', $tutorAcademico->id)
            ->where('is_active', true)
            ->get()
            ->groupBy('group_id');

        if ($schedules->isEmpty()) {
            return;
        }

        $statusCycle = ['PRESENTE', 'PRESENTE', 'PRESENTE', 'RETARDO', 'FALTA'];

        foreach ($schedules as $groupId => $groupSchedules) {
            $students = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))
                ->where('group_id', $groupId)
                ->get();

            if ($students->isEmpty()) {
                continue;
            }

            foreach ($groupSchedules as $schedule) {
                $classSession = $this->classSessionFor($schedule, $tutorAcademico);
                $device = $classSession->device;

                foreach ($students->values() as $index => $student) {
                    $status = $statusCycle[$index % count($statusCycle)];

                    Attendance::firstOrCreate(
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
                }
            }
        }
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
}
