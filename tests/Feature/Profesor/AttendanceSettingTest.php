<?php

use App\Models\AttendanceSetting;

test('returns the default tolerances when the schedule has no custom setting', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/schedule/{$schedule->id}/attendance-setting", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.id', null)
        ->assertJsonPath('data.present_tolerance_minutes', AttendanceSetting::DEFAULT_PRESENT_TOLERANCE_MINUTES)
        ->assertJsonPath('data.late_tolerance_minutes', AttendanceSetting::DEFAULT_LATE_TOLERANCE_MINUTES)
        ->assertJsonPath('data.schedule.id', $schedule->id);
});

test('creates a custom attendance setting for the teacher own schedule', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->putJson("/api/v1/profesor/schedule/{$schedule->id}/attendance-setting", [
        'present_tolerance_minutes' => 3,
        'late_tolerance_minutes' => 10,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.present_tolerance_minutes', 3)
        ->assertJsonPath('data.late_tolerance_minutes', 10);
    $this->assertDatabaseHas('attendance_settings', [
        'schedule_id' => $schedule->id,
        'present_tolerance_minutes' => 3,
        'late_tolerance_minutes' => 10,
        'is_active' => true,
    ]);
});

test('updates an existing custom attendance setting instead of duplicating it', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    AttendanceSetting::create(['schedule_id' => $schedule->id, 'present_tolerance_minutes' => 5, 'late_tolerance_minutes' => 15]);
    $token = fakeGovernanceAuth($teacher);

    $response = $this->putJson("/api/v1/profesor/schedule/{$schedule->id}/attendance-setting", [
        'present_tolerance_minutes' => 3,
        'late_tolerance_minutes' => 10,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    expect(AttendanceSetting::where('schedule_id', $schedule->id)->count())->toBe(1);
    $this->assertDatabaseHas('attendance_settings', ['schedule_id' => $schedule->id, 'late_tolerance_minutes' => 10]);
});

test('rejects a late tolerance not greater than the present tolerance', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->putJson("/api/v1/profesor/schedule/{$schedule->id}/attendance-setting", [
        'present_tolerance_minutes' => 10,
        'late_tolerance_minutes' => 10,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable()->assertJsonPath('error_code', 'VAL01');
});

test('rejects adjusting a schedule the teacher does not own', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule(governanceUserId: 2);
    $token = fakeGovernanceAuth($teacher);

    $response = $this->putJson("/api/v1/profesor/schedule/{$otherSchedule->id}/attendance-setting", [
        'present_tolerance_minutes' => 3,
        'late_tolerance_minutes' => 10,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('resets a custom attendance setting back to the defaults', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    AttendanceSetting::create(['schedule_id' => $schedule->id, 'present_tolerance_minutes' => 3, 'late_tolerance_minutes' => 10]);
    $token = fakeGovernanceAuth($teacher);

    $response = $this->deleteJson("/api/v1/profesor/schedule/{$schedule->id}/attendance-setting", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.present_tolerance_minutes', AttendanceSetting::DEFAULT_PRESENT_TOLERANCE_MINUTES)
        ->assertJsonPath('data.late_tolerance_minutes', AttendanceSetting::DEFAULT_LATE_TOLERANCE_MINUTES);
    $this->assertDatabaseHas('attendance_settings', ['schedule_id' => $schedule->id, 'is_active' => false]);
});
