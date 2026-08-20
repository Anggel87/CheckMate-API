<?php

namespace App\Services\Profesor;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ScheduleSessionStateService
{
    /**
     * Snapshot of "what does the pasar-lista screen look like right now" for a schedule on a
     * given date: whether a session is open, the full roster with each student's current
     * attendance status, and which attendance rows are new since $sinceAttendanceId (used by
     * the SSE stream to emit only what changed since the client's last known cursor).
     *
     * @return array{session: ?ClassSession, students: Collection<int, array<string, mixed>>, new_attendances: Collection<int, Attendance>}
     */
    public function snapshot(Schedule $schedule, string $date, int $sinceAttendanceId = 0): array
    {
        $session = ClassSession::where('schedule_id', $schedule->id)
            ->whereDate('date', $date)
            ->first();

        $students = $schedule->group->students()->active()->with('details')->get();

        $attendances = $session !== null
            ? Attendance::where('class_session_id', $session->id)->get()
            : collect();

        $attendanceByStudent = $attendances->keyBy('student_id');

        return [
            'session' => $session,
            'students' => $students->map(function ($student) use ($attendanceByStudent) {
                $attendance = $attendanceByStudent->get($student->id);

                return [
                    'id' => $student->id,
                    'full_name' => $student->fullName(),
                    'photo_url' => $student->photo ? Storage::disk('public')->url($student->photo) : null,
                    'status' => $attendance?->status,
                    'registered_at' => $attendance?->registered_at?->toIso8601String(),
                    'method' => $attendance?->method,
                    'nfc_uid' => $student->details?->nfc_uid,
                ];
            })->values(),
            'new_attendances' => $attendances->where('id', '>', $sinceAttendanceId)->values(),
        ];
    }
}
