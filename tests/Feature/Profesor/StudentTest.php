<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;

test('shows a student profile for a student the teacher teaches', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.group.id', $group->id);
});

test('rejects viewing a student outside the teacher groups', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $otherGroup = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 when the id does not belong to a student', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$teacher->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR01');
});

test('lists attendance only for subjects the teacher teaches the student', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $mySchedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $otherSchedule = makeActiveSchedule($group);

    Attendance::factory()->present()->create(['student_id' => $student->id, 'schedule_id' => $mySchedule->id]);
    Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $otherSchedule->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}/attendance", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'PRESENTE');
});

test('lists justifications only for subjects the teacher teaches the student', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $mySchedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $mySchedule->id]);
    Justification::factory()->create(['attendance_id' => $attendance->id, 'justified_by_user_id' => $student->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}/justifications", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data');
});
