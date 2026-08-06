<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\NfcTapRequest;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Services\Device\NfcTapService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class NfcController extends Controller
{
    use ApiResponse;

    public function handle(NfcTapRequest $request, NfcTapService $service): JsonResponse
    {
        $data = $request->validated();

        $result = $service->handle($data['mac_address'], $data['nfc_uid'], $data['scanned_at'] ?? null);

        if ($result instanceof ClassSession) {
            return $this->successResponse('Sesión de clase abierta correctamente.', [
                'event' => 'session_opened',
                'session_id' => $result->id,
                'opened_at' => $result->opened_at->toIso8601String(),
            ], 201);
        }

        /** @var Attendance $result */
        return $this->successResponse('Asistencia registrada correctamente.', [
            'event' => 'attendance_registered',
            'student_id' => $result->student_id,
            'full_name' => $result->student->fullName(),
            'status' => $result->status,
            'checked_in_at' => $result->registered_at->toIso8601String(),
        ], 201);
    }
}
