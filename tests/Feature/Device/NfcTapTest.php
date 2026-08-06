<?php

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Str;

test('a teacher tap opens a class session', function () {
    ['teacher' => $teacher, 'schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
    UserDetail::create(['user_id' => $teacher->id, 'nfc_uid' => 'AA:00:00:01', 'qr_uuid' => (string) Str::uuid()]);

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'AA:00:00:01',
    ]);

    $response->assertCreated()->assertJsonPath('data.event', 'session_opened');
    $this->assertDatabaseHas('class_sessions', [
        'schedule_id' => $schedule->id,
        'status' => 'ABIERTA',
        'opening_method' => 'NFC',
    ]);
});

test('a second teacher tap reports the session is already open', function () {
    ['teacher' => $teacher, 'schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
    UserDetail::create(['user_id' => $teacher->id, 'nfc_uid' => 'AA:00:00:01', 'qr_uuid' => (string) Str::uuid()]);
    makeOpenClassSession($schedule);

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'AA:00:00:01',
    ]);

    $response->assertConflict()->assertJsonPath('error_code', 'SES01');
});

test('registers an on time attendance for a student tap within tolerance', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
    UserDetail::create(['user_id' => $teacher->id, 'nfc_uid' => 'AA:00:00:01', 'qr_uuid' => (string) Str::uuid()]);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'BB:00:00:02', 'qr_uuid' => (string) Str::uuid()]);

    $this->postJson('/api/v1/device/nfc', ['mac_address' => $device->mac_address, 'nfc_uid' => 'AA:00:00:01']);
    $session = ClassSession::where('schedule_id', $schedule->id)->firstOrFail();

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'BB:00:00:02',
        'scanned_at' => $session->opened_at->copy()->addMinutes(5)->format('Y-m-d\TH:i:s'),
    ]);

    $response->assertCreated()->assertJsonPath('data.event', 'attendance_registered')->assertJsonPath('data.status', 'PRESENTE');
    $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'PRESENTE', 'method' => 'NFC']);
});

test('registers a late attendance for a student tap beyond tolerance', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
    UserDetail::create(['user_id' => $teacher->id, 'nfc_uid' => 'AA:00:00:01', 'qr_uuid' => (string) Str::uuid()]);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'BB:00:00:02', 'qr_uuid' => (string) Str::uuid()]);

    $this->postJson('/api/v1/device/nfc', ['mac_address' => $device->mac_address, 'nfc_uid' => 'AA:00:00:01']);
    $session = ClassSession::where('schedule_id', $schedule->id)->firstOrFail();

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'BB:00:00:02',
        'scanned_at' => $session->opened_at->copy()->addMinutes(15)->format('Y-m-d\TH:i:s'),
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'RETARDO');
});

test('rejects an unrecognized nfc card', function () {
    ['device' => $device] = makeScheduleCurrentlyInSession();

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'DD:00:00:04',
    ]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR02');
});

test('rejects a student tap for a student outside the schedule group', function () {
    ['teacher' => $teacher, 'schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
    UserDetail::create(['user_id' => $teacher->id, 'nfc_uid' => 'AA:00:00:01', 'qr_uuid' => (string) Str::uuid()]);
    makeOpenClassSession($schedule);

    $otherGroup = makeActiveGroup();
    $outsider = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);
    UserDetail::create(['user_id' => $outsider->id, 'nfc_uid' => 'CC:00:00:03', 'qr_uuid' => (string) Str::uuid()]);

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'CC:00:00:03',
    ]);

    $response->assertForbidden()->assertJsonPath('error_code', 'ATT02');
});

test('rejects a duplicate student tap in the same session', function () {
    ['group' => $group, 'schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
    $session = makeOpenClassSession($schedule);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'BB:00:00:02', 'qr_uuid' => (string) Str::uuid()]);

    Attendance::factory()->create([
        'class_session_id' => $session->id,
        'student_id' => $student->id,
        'schedule_id' => $schedule->id,
        'devices_id' => $session->device_id,
    ]);

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'BB:00:00:02',
    ]);

    $response->assertConflict()->assertJsonPath('error_code', 'ATT01');
});

test('rejects a student tap before the teacher has opened the session', function () {
    ['group' => $group, 'device' => $device] = makeScheduleCurrentlyInSession();

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'BB:00:00:02', 'qr_uuid' => (string) Str::uuid()]);

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'BB:00:00:02',
    ]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SES02');
});

test('rejects a tap when there is no class scheduled right now for the classroom', function () {
    $device = Device::factory()->create();

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'EE:00:00:05',
    ]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SES04');
});

test('rejects a tap from an unregistered device', function () {
    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'nfc_uid' => 'EE:00:00:05',
    ]);

    $response->assertNotFound()->assertJsonPath('error_code', 'DEV01');
});

test('rejects a tap from a deactivated device', function () {
    $device = Device::factory()->inactive()->create();

    $response = $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => 'EE:00:00:05',
    ]);

    $response->assertForbidden()->assertJsonPath('error_code', 'DEV04');
});
