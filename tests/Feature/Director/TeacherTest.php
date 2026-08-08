<?php

test('lists only teachers with a schedule in the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['teacher' => $teacher] = makeTeacherWithSchedule($group, null, 2);

    $otherGroup = makeActiveGroup();
    makeTeacherWithSchedule($otherGroup, null, 3);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/teachers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $teacher->id);
});

test('shows a teacher within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['teacher' => $teacher] = makeTeacherWithSchedule($group, null, 2);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/teachers/{$teacher->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.id', $teacher->id);
});

test('rejects a teacher outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();
    ['teacher' => $otherTeacher] = makeTeacherWithSchedule($otherGroup, null, 2);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/teachers/{$otherTeacher->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns class attendance for a teacher schedule', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson(
        "/api/v1/director-carrera/teachers/{$teacher->id}/class-attendance?schedule_id={$schedule->id}",
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertOk();
});
