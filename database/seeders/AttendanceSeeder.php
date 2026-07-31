<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceSeeder extends Seeder
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

    public function run(): void
    {
        // Solo un horario por grupo y una sesión pasada por horario, para tener datos
        // de prueba realistas sin inflar la tabla de asistencias.
        $schedules = Schedule::query()
            ->where('is_active', true)
            ->whereHas('schoolYear', fn ($query) => $query->where('status', 'ACTIVO'))
            ->with(['group.students'])
            ->get()
            ->unique('group_id');

        foreach ($schedules as $schedule) {
            $device = Device::firstOrCreate(
                ['classroom_id' => $schedule->classroom_id],
                [
                    'mac_address' => strtoupper(fake()->unique()->regexify('([0-9A-F]{2}:){5}[0-9A-F]{2}')),
                    'ip' => fake()->localIpv4(),
                    'is_active' => true,
                ]
            );

            $students = $schedule->group->students;

            if ($students->isEmpty()) {
                continue;
            }

            foreach ($this->pastSessionDates($schedule->day_of_week, 1) as $date) {
                $this->createSession($schedule, $device, $students, $date);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function createSession(Schedule $schedule, Device $device, $students, Carbon $date): void
    {
        $openedAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->start_time);
        $closedAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->end_time);

        $classSession = ClassSession::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $schedule->teacher_id,
            'device_id' => $device->id,
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'status' => 'CERRADA',
            'opening_method' => 'NFC',
            'is_active' => false,
        ]);

        foreach ($students as $student) {
            $status = fake()->randomElement(['PRESENTE', 'PRESENTE', 'PRESENTE', 'RETARDO', 'FALTA']);

            Attendance::create([
                'class_session_id' => $classSession->id,
                'student_id' => $student->id,
                'schedule_id' => $schedule->id,
                'devices_id' => $device->id,
                'registered_at' => $status === 'FALTA'
                    ? $closedAt
                    : $openedAt->copy()->addMinutes(fake()->numberBetween(0, 15)),
                'status' => $status,
                'method' => $status === 'FALTA' ? 'SISTEMA' : 'NFC',
            ]);
        }
    }

    /**
     * @return array<int, Carbon>
     */
    private function pastSessionDates(string $dayOfWeek, int $count): array
    {
        $cursor = Carbon::now()->previous(self::DAY_CONSTANTS[$dayOfWeek] ?? Carbon::MONDAY);
        $dates = [];

        for ($i = 0; $i < $count; $i++) {
            $dates[] = $cursor->copy();
            $cursor->subWeek();
        }

        return $dates;
    }
}
