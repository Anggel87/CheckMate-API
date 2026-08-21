<?php

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Str;

test('opens a class session for the teacher own schedule', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    Device::factory()->create(['classroom_id' => $schedule->classroom_id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson('/api/v1/profesor/sessions/open', [
        'schedule_id' => $schedule->id,
        'date' => now()->format('Y-m-d'),
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.status', 'ABIERTA');
    $this->assertDatabaseHas('class_sessions', ['schedule_id' => $schedule->id, 'status' => 'ABIERTA']);
});

test('rejects opening a session for a schedule the teacher does not own', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule(governanceUserId: 2);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson('/api/v1/profesor/sessions/open', [
        'schedule_id' => $otherSchedule->id,
        'date' => now()->format('Y-m-d'),
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('rejects opening two sessions for the same schedule on the same day', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    makeOpenClassSession($schedule);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson('/api/v1/profesor/sessions/open', [
        'schedule_id' => $schedule->id,
        'date' => now()->format('Y-m-d'),
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SES01');
});

test('registers an on time attendance via nfc', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['start_time' => '08:00:00', 'end_time' => '09:00:00']);
    $session = makeOpenClassSession($schedule);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'AA:BB:CC:DD', 'qr_uuid' => (string) Str::uuid()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => 'AA:BB:CC:DD',
        'scanned_at' => $session->date->format('Y-m-d').'T08:05:00',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.status', 'PRESENTE');
    $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'PRESENTE', 'method' => 'NFC']);
    $this->assertDatabaseHas('audit_logs', ['entity' => 'attendance', 'action' => 'CREATE', 'performed_by_user_id' => $teacher->id]);
});

test('registers a late attendance via nfc beyond the present tolerance', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['start_time' => '08:00:00', 'end_time' => '09:00:00']);
    $session = makeOpenClassSession($schedule);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'AA:BB:CC:DD', 'qr_uuid' => (string) Str::uuid()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => 'AA:BB:CC:DD',
        'scanned_at' => $session->date->format('Y-m-d').'T08:20:00',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.status', 'RETARDO');
});

test('rejects an nfc scan beyond the late tolerance as no longer valid', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['start_time' => '08:00:00', 'end_time' => '09:00:00']);
    $session = makeOpenClassSession($schedule);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'AA:BB:CC:DD', 'qr_uuid' => (string) Str::uuid()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => 'AA:BB:CC:DD',
        'scanned_at' => $session->date->format('Y-m-d').'T08:35:00',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'ATT05');
    $this->assertDatabaseMissing('attendances', ['student_id' => $student->id]);
});

test('honors a custom attendance setting tolerance when scanning via nfc', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['start_time' => '08:00:00', 'end_time' => '09:00:00']);
    AttendanceSetting::create([
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 3,
        'late_tolerance_minutes' => 10,
        'is_active' => true,
    ]);
    $session = makeOpenClassSession($schedule);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'AA:BB:CC:DD', 'qr_uuid' => (string) Str::uuid()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => 'AA:BB:CC:DD',
        'scanned_at' => $session->date->format('Y-m-d').'T08:12:00',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'ATT05');
});

test('rejects nfc scan for an unrecognized card', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => 'FF:FF:FF:FF',
        'scanned_at' => now()->format('Y-m-d\TH:i:s'),
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR02');
});

test('rejects nfc scan for a student outside the session group', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);

    $otherGroup = makeActiveGroup();
    $outsider = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);
    UserDetail::create(['user_id' => $outsider->id, 'nfc_uid' => '11:22:33:44', 'qr_uuid' => (string) Str::uuid()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => '11:22:33:44',
        'scanned_at' => now()->format('Y-m-d\TH:i:s'),
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'ATT02');
});

test('rejects a duplicate nfc scan in the same session', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'AA:BB:CC:DD', 'qr_uuid' => (string) Str::uuid()]);

    Attendance::factory()->create([
        'class_session_id' => $session->id,
        'student_id' => $student->id,
        'schedule_id' => $schedule->id,
        'devices_id' => $session->device_id,
    ]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/nfc", [
        'nfc_uid' => 'AA:BB:CC:DD',
        'scanned_at' => now()->format('Y-m-d\TH:i:s'),
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'ATT01');
});

test('manually overrides a student attendance status', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->patchJson("/api/v1/profesor/sessions/{$session->id}/students/{$student->id}", [
        'status' => 'FALTA',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'FALTA');
    $this->assertDatabaseHas('attendances', ['class_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'FALTA', 'method' => 'MANUAL']);
});

test('closes a session and marks unregistered students as absent', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);

    $registered = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    $missing = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    Attendance::factory()->present()->create([
        'class_session_id' => $session->id,
        'student_id' => $registered->id,
        'schedule_id' => $schedule->id,
        'devices_id' => $session->device_id,
    ]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/close", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'CERRADA')
        ->assertJsonPath('data.on_time', 1)
        ->assertJsonPath('data.absent', 1);

    $this->assertDatabaseHas('attendances', ['student_id' => $missing->id, 'status' => 'FALTA', 'method' => 'SISTEMA']);
    $this->assertDatabaseHas('audit_logs', ['entity' => 'attendance', 'action' => 'CREATE', 'performed_by_user_id' => $teacher->id]);
    expect(ClassSession::find($session->id)->status)->toBe('CERRADA');
});

test('rejects closing an already closed session', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);
    $session->update(['status' => 'CERRADA', 'closed_at' => now(), 'is_active' => false]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/sessions/{$session->id}/close", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SES03');
});
