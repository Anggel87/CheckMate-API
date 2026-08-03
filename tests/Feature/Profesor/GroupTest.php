<?php

use App\Models\User;

test('lists groups where the teacher has an active schedule', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();

    User::factory()->student()->count(2)->create(['governance_user_id' => null, 'group_id' => $group->id]);
    User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id, 'active' => false]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/groups', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $group->id)
        ->assertJsonPath('data.0.student_count', 2);
});

test('lists active students of a group the teacher teaches', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/groups/{$group->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $student->id);
});

test('rejects listing students of a group the teacher does not teach', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $otherGroup = makeActiveGroup();

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/groups/{$otherGroup->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 for a nonexistent group', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/groups/999999/students', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'GRP02');
});
