<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profesor\NfcAttendanceRequest;
use App\Http\Requests\Profesor\OpenSessionRequest;
use App\Http\Requests\Profesor\UpdateSessionStudentRequest;
use App\Http\Resources\ClassSessionResource;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Services\Profesor\CloseClassSessionService;
use App\Services\Profesor\OpenClassSessionService;
use App\Services\Profesor\RegisterNfcAttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    use ApiResponse;

    public function open(OpenSessionRequest $request, OpenClassSessionService $service): JsonResponse
    {
        $data = $request->validated();

        $session = $service->open($request->user(), (int) $data['schedule_id'], $data['date']);

        return $this->successResponse('Sesión abierta correctamente.', new ClassSessionResource($session), 201);
    }

    public function nfc(NfcAttendanceRequest $request, int $session, RegisterNfcAttendanceService $service): JsonResponse
    {
        $data = $request->validated();

        $attendance = $service->register($request->user(), $session, $data['nfc_uid'], $data['scanned_at']);

        return $this->successResponse('Asistencia registrada correctamente.', [
            'student_id' => $attendance->student->id,
            'full_name' => $attendance->student->fullName(),
            'status' => $attendance->status,
            'checked_in_at' => $attendance->registered_at->toIso8601String(),
        ], 201);
    }

    public function updateStudent(UpdateSessionStudentRequest $request, int $session, int $student): JsonResponse
    {
        $sessionModel = ClassSession::find($session);

        if ($sessionModel === null || $sessionModel->teacher_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $data = $request->validated();

        $attendance = Attendance::updateOrCreate(
            ['class_session_id' => $sessionModel->id, 'student_id' => $student],
            [
                'schedule_id' => $sessionModel->schedule_id,
                'devices_id' => $sessionModel->device_id,
                'registered_at' => now(),
                'status' => $data['status'],
                'method' => 'MANUAL',
            ]
        );

        return $this->successResponse('Asistencia actualizada correctamente.', [
            'student_id' => $attendance->student_id,
            'status' => $attendance->status,
            'updated_at' => $attendance->updated_at->toIso8601String(),
        ]);
    }

    public function close(Request $request, int $session, CloseClassSessionService $service): JsonResponse
    {
        $summary = $service->close($request->user(), $session);

        return $this->successResponse('Sesión cerrada correctamente.', $summary);
    }
}
