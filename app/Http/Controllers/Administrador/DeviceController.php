<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\PingsDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreDeviceRequest;
use App\Http\Requests\Administrador\UpdateDeviceRequest;
use App\Http\Resources\AdminDeviceResource;
use App\Models\Classroom;
use App\Models\Device;
use App\Services\AuditLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse, PingsDevice;

    /** @var list<string> */
    private const AUDIT_FIELDS = ['mac_address', 'ip', 'is_active', 'classroom_id'];

    public function index(Request $request): JsonResponse
    {
        $devices = Device::query()
            ->when($request->query('classroom_id'), fn ($query, $classroomId) => $query->where('classroom_id', $classroomId))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->get();

        return $this->successResponse('Dispositivos obtenidos correctamente.', AdminDeviceResource::collection($devices));
    }

    public function show(int $device): JsonResponse
    {
        $model = Device::find($device);

        if ($model === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        return $this->successResponse('Dispositivo obtenido correctamente.', new AdminDeviceResource($model));
    }

    public function ping(int $device): JsonResponse
    {
        $model = Device::find($device);

        if ($model === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        $this->pingDevice($model);

        return $this->successResponse('El dispositivo está en línea.', ['status' => 'ONLINE']);
    }

    public function store(StoreDeviceRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $data = $request->validated();

        if (! Classroom::whereKey($data['classroom_id'])->exists()) {
            throw ApiException::notFound('El salón indicado no existe.', 'CLS01');
        }

        if (Device::where('mac_address', strtoupper($data['mac_address']))->exists()) {
            throw ApiException::conflict('Ya existe un dispositivo registrado con esa dirección MAC.', 'DEV03');
        }

        $device = Device::create([...$data, 'mac_address' => strtoupper($data['mac_address']), 'is_active' => true]);

        $auditLogger->log('device', $device->id, 'CREATE', $request->user()->id, null, $device->only(self::AUDIT_FIELDS));

        return $this->successResponse('Dispositivo creado correctamente.', new AdminDeviceResource($device), 201);
    }

    public function update(UpdateDeviceRequest $request, int $device, AuditLogger $auditLogger): JsonResponse
    {
        $model = Device::find($device);

        if ($model === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        $before = $model->only(self::AUDIT_FIELDS);
        $data = $request->validated();

        if (isset($data['classroom_id']) && ! Classroom::whereKey($data['classroom_id'])->exists()) {
            throw ApiException::notFound('El salón indicado no existe.', 'CLS01');
        }

        $model->update($data);

        $auditLogger->log('device', $model->id, 'UPDATE', $request->user()->id, $before, $model->only(self::AUDIT_FIELDS));

        return $this->successResponse('Dispositivo actualizado correctamente.', new AdminDeviceResource($model));
    }

    public function destroy(Request $request, int $device, AuditLogger $auditLogger): JsonResponse
    {
        $model = Device::find($device);

        if ($model === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        if (! $model->is_active) {
            throw ApiException::conflict('El dispositivo ya se encuentra dado de baja.', 'DEV04');
        }

        $before = $model->only(self::AUDIT_FIELDS);
        $model->update(['is_active' => false]);

        $auditLogger->log('device', $model->id, 'DELETE', $request->user()->id, $before, $model->only(self::AUDIT_FIELDS));

        return $this->successResponse('Dispositivo dado de baja correctamente.', new AdminDeviceResource($model));
    }
}
