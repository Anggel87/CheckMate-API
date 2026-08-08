<?php

use App\Models\AttendanceSetting;
use App\Models\Schedule;

test('creates an attendance setting for a schedule', function () {
    $schedule = Schedule::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/attendance-settings', [
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 30,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.present_tolerance_minutes', 10);
    $this->assertDatabaseHas('attendance_settings', ['schedule_id' => $schedule->id, 'present_tolerance_minutes' => 10]);
});

test('rejects a setting for a missing schedule', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/attendance-settings', [
        'schedule_id' => 999999,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 30,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SCH01');
});

test('rejects a duplicate setting for the same schedule', function () {
    $schedule = Schedule::factory()->create();
    AttendanceSetting::create([
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 30,
        'allow_manual_attendance' => false,
        'is_active' => true,
    ]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/attendance-settings', [
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 5,
        'late_tolerance_minutes' => 15,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'ATS02');
});

test('rejects a late tolerance not greater than the present tolerance', function () {
    $schedule = Schedule::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/attendance-settings', [
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 10,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('updates an attendance setting', function () {
    $schedule = Schedule::factory()->create();
    $setting = AttendanceSetting::create([
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 30,
        'allow_manual_attendance' => false,
        'is_active' => true,
    ]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/attendance-settings/{$setting->id}", [
        'present_tolerance_minutes' => 5,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.present_tolerance_minutes', 5);
});

test('rejects an update that leaves late tolerance not greater than present', function () {
    $schedule = Schedule::factory()->create();
    $setting = AttendanceSetting::create([
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 30,
        'allow_manual_attendance' => false,
        'is_active' => true,
    ]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/attendance-settings/{$setting->id}", [
        'present_tolerance_minutes' => 40,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('deactivates an attendance setting', function () {
    $schedule = Schedule::factory()->create();
    $setting = AttendanceSetting::create([
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 30,
        'allow_manual_attendance' => false,
        'is_active' => true,
    ]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/attendance-settings/{$setting->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('attendance_settings', ['id' => $setting->id, 'is_active' => false]);
});

test('returns 404 for a missing attendance setting', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/attendance-settings/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'ATS01');
});
