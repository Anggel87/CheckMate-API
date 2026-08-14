<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;

test('shows a justification for a student the teacher teaches', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $justification = Justification::factory()->create([
        'attendance_id' => $attendance->id,
        'justified_by_user_id' => $student->id,
    ]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/justifications/{$justification->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.id', $justification->id)
        ->assertJsonPath('data.reason', $justification->reason);
});

test('rejects viewing a justification outside the teacher schedules', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $otherGroup = makeActiveGroup();
    $otherSchedule = makeActiveSchedule($otherGroup);
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $otherSchedule->id]);
    $justification = Justification::factory()->create([
        'attendance_id' => $attendance->id,
        'justified_by_user_id' => $student->id,
    ]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/justifications/{$justification->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 when the justification does not exist', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/justifications/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound();
});
