<?php

use App\Models\Device;
use Illuminate\Support\Facades\Http;

test('lists only devices in classrooms used by the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $device = Device::factory()->create(['classroom_id' => $schedule->classroom_id]);

    $otherGroup = makeActiveGroup();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule($otherGroup, null, 3);
    Device::factory()->create(['classroom_id' => $otherSchedule->classroom_id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/devices', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $device->id);
});

test('rejects a device outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule($otherGroup, null, 2);
    $otherDevice = Device::factory()->create(['classroom_id' => $otherSchedule->classroom_id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/devices/{$otherDevice->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('pings a device within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $device = Device::factory()->create(['classroom_id' => $schedule->classroom_id, 'ip' => '10.0.0.9']);
    Http::fake(['http://10.0.0.9*' => Http::response('', 200)]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/devices/{$device->id}/ping", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'ONLINE');
});
