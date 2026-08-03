<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;

test('approves a pending justification and marks the attendance as justified', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);
    $schedule = makeActiveSchedule($group);
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $justification = Justification::factory()->create(['attendance_id' => $attendance->id, 'justified_by_user_id' => $student->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->patchJson("/api/v1/tutor/students/{$student->id}/justifications/{$justification->id}", [
        'status' => 'ACEPTADO',
        'comment' => 'Justificante válido.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'ACEPTADO')
        ->assertJsonPath('data.reviewed_by', $tutor->fullName());

    $this->assertDatabaseHas('justifications', ['id' => $justification->id, 'status' => 'ACEPTADO', 'reviewed_by_user_id' => $tutor->id]);
    $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'JUSTIFICADA']);
});

test('rejects reviewing a justification already reviewed', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);
    $schedule = makeActiveSchedule($group);
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $justification = Justification::factory()->accepted()->create(['attendance_id' => $attendance->id, 'justified_by_user_id' => $student->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->patchJson("/api/v1/tutor/students/{$student->id}/justifications/{$justification->id}", [
        'status' => 'RECHAZADO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'JUST02');
});

test('rejects reviewing a justification for a student outside the tutor groups', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);

    $otherGroup = makeActiveGroup();
    $otherSchedule = makeActiveSchedule($otherGroup);
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);
    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $otherSchedule->id]);
    $justification = Justification::factory()->create(['attendance_id' => $attendance->id, 'justified_by_user_id' => $student->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->patchJson("/api/v1/tutor/students/{$student->id}/justifications/{$justification->id}", [
        'status' => 'ACEPTADO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 for a justification that does not exist', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->patchJson("/api/v1/tutor/students/{$student->id}/justifications/999999", [
        'status' => 'ACEPTADO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'JUST01');
});
