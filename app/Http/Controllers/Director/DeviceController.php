<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\PingsDevice;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminDeviceResource;
use App\Models\Device;
use App\Models\User;
use App\Services\Director\CareerScope;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse, PingsDevice;

    public function index(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());

        $devices = Device::whereIn('id', $scope->deviceIds($careerIds))->get();

        return $this->successResponse('Dispositivos obtenidos correctamente.', AdminDeviceResource::collection($devices));
    }

    public function show(Request $request, int $device, CareerScope $scope): JsonResponse
    {
        $model = $this->findDevice($request->user(), $device, $scope);

        return $this->successResponse('Dispositivo obtenido correctamente.', new AdminDeviceResource($model));
    }

    public function ping(Request $request, int $device, CareerScope $scope): JsonResponse
    {
        $model = $this->findDevice($request->user(), $device, $scope);

        $this->pingDevice($model);

        return $this->successResponse('El dispositivo está en línea.', ['status' => 'ONLINE']);
    }

    private function findDevice(User $director, int $id, CareerScope $scope): Device
    {
        $device = Device::find($id);

        if ($device === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        $careerIds = $scope->careerIds($director);

        if (! $scope->deviceIds($careerIds)->contains($device->id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $device;
    }
}
