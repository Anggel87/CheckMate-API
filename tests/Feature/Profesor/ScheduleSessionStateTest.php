<?php

use App\Models\Attendance;
use App\Models\User;

test('returns a null session and every student unmarked when nothing has been checked in', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/schedule/{$schedule->id}/session", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.session', null)
        ->assertJsonCount(1, 'data.students')
        ->assertJsonPath('data.students.0.status', null);
});

test('returns the open session and each student current attendance status', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $session = makeOpenClassSession($schedule);

    $checkedIn = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    Attendance::factory()->present()->create([
        'class_session_id' => $session->id,
        'student_id' => $checkedIn->id,
        'schedule_id' => $schedule->id,
        'devices_id' => $session->device_id,
    ]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/schedule/{$schedule->id}/session", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.session.id', $session->id)
        ->assertJsonPath('data.session.status', 'ABIERTA')
        ->assertJsonCount(2, 'data.students');

    $students = collect($response->json('data.students'));
    expect($students->firstWhere('id', $checkedIn->id)['status'])->toBe('PRESENTE');
});

test('rejects fetching session state for a schedule the teacher does not own', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule(governanceUserId: 2);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/schedule/{$otherSchedule->id}/session", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 for a nonexistent schedule', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/schedule/999999/session', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SCH01');
});
