<?php

use App\Models\Classroom;
use App\Models\Device;
use Illuminate\Support\Facades\Http;

test('creates a device for an existing classroom', function () {
    $classroom = Classroom::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/devices', [
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'classroom_id' => $classroom->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.mac_address', 'AA:BB:CC:DD:EE:FF');
    $this->assertDatabaseHas('audit_logs', ['entity' => 'device', 'entity_id' => $response->json('data.id'), 'action' => 'CREATE']);
});

test('rejects a device for a missing classroom', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/devices', [
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'classroom_id' => 999999,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'CLS01');
});

test('rejects a duplicate mac address', function () {
    $existing = Device::factory()->create();
    $classroom = Classroom::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/devices', [
        'mac_address' => $existing->mac_address,
        'classroom_id' => $classroom->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'DEV03');
});

test('deactivates a device', function () {
    $device = Device::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/devices/{$device->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('devices', ['id' => $device->id, 'is_active' => false]);
    $this->assertDatabaseHas('audit_logs', ['entity' => 'device', 'entity_id' => $device->id, 'action' => 'DELETE']);
});

test('rejects deactivating an already deactivated device', function () {
    $device = Device::factory()->inactive()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/devices/{$device->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'DEV04');
});

test('pings a device that responds successfully', function () {
    $device = Device::factory()->create(['ip' => '10.0.0.5']);
    Http::fake(['http://10.0.0.5*' => Http::response('', 200)]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/devices/{$device->id}/ping", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'ONLINE');
});

test('reports a device offline when it does not respond', function () {
    $device = Device::factory()->create(['ip' => '10.0.0.6']);
    Http::fake(['http://10.0.0.6*' => Http::response('', 500)]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/devices/{$device->id}/ping", ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(503)->assertJsonPath('error_code', 'DEV02');
});
