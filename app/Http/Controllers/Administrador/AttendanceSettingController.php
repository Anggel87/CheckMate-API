<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreAttendanceSettingRequest;
use App\Http\Requests\Administrador\UpdateAttendanceSettingRequest;
use App\Http\Resources\AdminAttendanceSettingResource;
use App\Models\AttendanceSetting;
use App\Models\Schedule;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSettingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $settings = AttendanceSetting::query()
            ->with('schedule.subject', 'schedule.group')
            ->when($request->query('schedule_id'), fn ($query, $scheduleId) => $query->where('schedule_id', $scheduleId))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->get();

        return $this->successResponse('Configuraciones de asistencia obtenidas correctamente.', AdminAttendanceSettingResource::collection($settings));
    }

    public function show(int $attendanceSetting): JsonResponse
    {
        $model = $this->findSetting($attendanceSetting)->load('schedule.subject', 'schedule.group');

        return $this->successResponse('Configuración de asistencia obtenida correctamente.', new AdminAttendanceSettingResource($model));
    }

    public function store(StoreAttendanceSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! Schedule::whereKey($data['schedule_id'])->exists()) {
            throw ApiException::notFound('El horario solicitado no existe.', 'SCH01');
        }

        if (AttendanceSetting::where('schedule_id', $data['schedule_id'])->exists()) {
            throw ApiException::conflict('Ese horario ya tiene una configuración de asistencia.', 'ATS02');
        }

        $setting = AttendanceSetting::create([...$data, 'is_active' => true]);

        return $this->successResponse('Configuración de asistencia creada correctamente.', new AdminAttendanceSettingResource($setting), 201);
    }

    public function update(UpdateAttendanceSettingRequest $request, int $attendanceSetting): JsonResponse
    {
        $model = $this->findSetting($attendanceSetting);
        $data = $request->validated();

        $present = $data['present_tolerance_minutes'] ?? $model->present_tolerance_minutes;
        $late = $data['late_tolerance_minutes'] ?? $model->late_tolerance_minutes;

        if ($late <= $present) {
            throw ApiException::unprocessable(
                'La tolerancia de retardo debe ser mayor a la de asistencia a tiempo.',
                'VAL01',
                ['late_tolerance_minutes' => ['La tolerancia de retardo debe ser mayor a la de asistencia a tiempo.']]
            );
        }

        $model->update($data);

        return $this->successResponse('Configuración de asistencia actualizada correctamente.', new AdminAttendanceSettingResource($model));
    }

    public function destroy(int $attendanceSetting): JsonResponse
    {
        $model = $this->findSetting($attendanceSetting);
        $model->update(['is_active' => false]);

        return $this->successResponse('Configuración de asistencia desactivada correctamente.', new AdminAttendanceSettingResource($model));
    }

    private function findSetting(int $id): AttendanceSetting
    {
        $setting = AttendanceSetting::find($id);

        if ($setting === null) {
            throw ApiException::notFound('La configuración de asistencia solicitada no existe.', 'ATS01');
        }

        return $setting;
    }
}
