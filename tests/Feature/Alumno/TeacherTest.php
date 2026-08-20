<?php

use App\Models\Subject;
use App\Models\User;

test('lists the teachers who teach the student group, grouped with their subjects', function () {
    $group = makeActiveGroup();
    $math = Subject::factory()->create();
    $physics = Subject::factory()->create();

    ['teacher' => $teacher] = makeTeacherWithSchedule($group, $math, 2);
    makeActiveSchedule($group, $physics)->update(['teacher_id' => $teacher->id]);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/teachers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $teacher->id)
        ->assertJsonPath('data.0.email', $teacher->email)
        ->assertJsonPath('data.0.is_tutor', false)
        ->assertJsonCount(2, 'data.0.subjects');
});

test('lists multiple teachers separately', function () {
    $group = makeActiveGroup();
    ['teacher' => $first] = makeTeacherWithSchedule($group, null, 2);
    ['teacher' => $second] = makeTeacherWithSchedule($group, null, 3);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/teachers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(2, 'data');
    expect($response->json('data.*.id'))->toEqualCanonicalizing([$first->id, $second->id]);
});

test('flags the teacher that is the student active academic tutor', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group, 2);
    makeActiveSchedule($group)->update(['teacher_id' => $tutor->id]);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/teachers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.0.id', $tutor->id)
        ->assertJsonPath('data.0.is_tutor', true);
});

test('does not flag a teacher as tutor for another group', function () {
    $group = makeActiveGroup();
    $otherGroup = makeActiveGroup();
    makeTutorForGroup($otherGroup, 2);

    ['teacher' => $teacher] = makeTeacherWithSchedule($group, null, 3);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/teachers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.0.id', $teacher->id)
        ->assertJsonPath('data.0.is_tutor', false);
});

test('returns an empty list for a student without a group', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => null]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/teachers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(0, 'data');
});

test('shows the detail of a teacher who teaches the student, including their schedules', function () {
    $group = makeActiveGroup();
    $math = Subject::factory()->create();
    $physics = Subject::factory()->create();

    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule($group, $math, 2);
    makeActiveSchedule($group, $physics)->update(['teacher_id' => $teacher->id]);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/teachers/{$teacher->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.id', $teacher->id)
        ->assertJsonPath('data.email', $teacher->email)
        ->assertJsonPath('data.is_tutor', false)
        ->assertJsonCount(2, 'data.subjects')
        ->assertJsonCount(2, 'data.schedules')
        ->assertJsonPath('data.schedules.0.schedule_id', $schedule->id);
});

test('flags is_tutor true on the teacher detail when they are the active academic tutor', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group, 2);
    makeActiveSchedule($group)->update(['teacher_id' => $tutor->id]);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/teachers/{$tutor->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.is_tutor', true);
});

test('rejects viewing a teacher that does not teach the student', function () {
    $group = makeActiveGroup();
    $otherGroup = makeActiveGroup();
    ['teacher' => $otherTeacher] = makeTeacherWithSchedule($otherGroup, null, 2);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/teachers/{$otherTeacher->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});
