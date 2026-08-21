<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profesor\UpdateAttendanceSettingRequest;
use App\Http\Resources\AdminAttendanceSettingResource;
use App\Models\AttendanceSetting;
use App\Models\Schedule;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Permite a un profesor/tutor academico ajustar la tolerancia de asistencia de sus
 * propios horarios (a los cuantos minutos ya es retardo, a los cuantos ya no es
 * valido el tap). Reusa la misma tabla attendance_settings del CRUD de Administrador,
 * solo que aqui el horario viene de la URL y la propiedad se valida por teacher_id.
 */
class AttendanceSettingController extends Controller
{
    use ApiResponse;

    public function show(Request $request, int $schedule): JsonResponse
    {
        $scheduleModel = $this->findOwnedSchedule($request, $schedule)->load('subject', 'group');
        $setting = AttendanceSetting::where('schedule_id', $scheduleModel->id)->first() ?? $this->defaults($scheduleModel);
        $setting->setRelation('schedule', $scheduleModel);

        return $this->successResponse('Configuración de asistencia obtenida correctamente.', new AdminAttendanceSettingResource($setting));
    }

    public function update(UpdateAttendanceSettingRequest $request, int $schedule): JsonResponse
    {
        $scheduleModel = $this->findOwnedSchedule($request, $schedule)->load('subject', 'group');
        $data = $request->validated();

        $setting = AttendanceSetting::updateOrCreate(
            ['schedule_id' => $scheduleModel->id],
            [...$data, 'is_active' => true],
        );
        $setting->setRelation('schedule', $scheduleModel);

        return $this->successResponse('Configuración de asistencia actualizada correctamente.', new AdminAttendanceSettingResource($setting));
    }

    public function destroy(Request $request, int $schedule): JsonResponse
    {
        $scheduleModel = $this->findOwnedSchedule($request, $schedule)->load('subject', 'group');

        AttendanceSetting::where('schedule_id', $scheduleModel->id)->update(['is_active' => false]);

        $setting = $this->defaults($scheduleModel);
        $setting->setRelation('schedule', $scheduleModel);

        return $this->successResponse('Configuración de asistencia restablecida a los valores por defecto.', new AdminAttendanceSettingResource($setting));
    }

    private function findOwnedSchedule(Request $request, int $scheduleId): Schedule
    {
        $schedule = Schedule::find($scheduleId);

        if ($schedule === null) {
            throw ApiException::notFound('El horario solicitado no existe.', 'SCH01');
        }

        if ($schedule->teacher_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $schedule;
    }

    private function defaults(Schedule $schedule): AttendanceSetting
    {
        return new AttendanceSetting([
            'schedule_id' => $schedule->id,
            'present_tolerance_minutes' => AttendanceSetting::DEFAULT_PRESENT_TOLERANCE_MINUTES,
            'late_tolerance_minutes' => AttendanceSetting::DEFAULT_LATE_TOLERANCE_MINUTES,
            'allow_manual_attendance' => false,
            'is_active' => true,
        ]);
    }
}
