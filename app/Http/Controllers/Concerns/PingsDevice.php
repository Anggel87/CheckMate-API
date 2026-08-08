<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use App\Models\Device;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

trait PingsDevice
{
    protected function pingDevice(Device $device): void
    {
        if ($device->ip === null) {
            throw new ApiException('El dispositivo no respondió. Verifica su conexión.', 503, 'DEV02');
        }

        try {
            $response = Http::timeout(5)->get("http://{$device->ip}");
        } catch (ConnectionException) {
            throw new ApiException('El dispositivo no respondió. Verifica su conexión.', 503, 'DEV02');
        }

        if (! $response->successful()) {
            throw new ApiException('El dispositivo no respondió. Verifica su conexión.', 503, 'DEV02');
        }
    }
}
