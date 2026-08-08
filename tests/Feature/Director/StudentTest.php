<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;

test('shows a student within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/students/{$student->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.id', $student->id);
});

test('rejects a student outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();
    $otherStudent = User::factory()->student()->create(['group_id' => $otherGroup->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/students/{$otherStudent->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('lists a student attendance history', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $schedule = makeActiveSchedule($group);
    $attendance = Attendance::factory()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id, 'status' => 'FALTA']);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/students/{$student->id}/attendance", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.0.status', 'FALTA');
});

test('lists a student justifications', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $schedule = makeActiveSchedule($group);
    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $justification = Justification::factory()->create(['attendance_id' => $attendance->id, 'justified_by_user_id' => $student->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/students/{$student->id}/justifications", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.0.id', $justification->id);
});
