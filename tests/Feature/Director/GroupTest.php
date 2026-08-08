<?php

use App\Models\User;

test('lists only groups within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $otherGroup = makeActiveGroup();

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/groups', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $group->id);
});

test('shows a group within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/groups/{$group->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.id', $group->id);
});

test('rejects a group outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/groups/{$otherGroup->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 for a missing group', function () {
    ['director' => $director] = makeCareerDirector();

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/groups/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'GRP02');
});

test('lists active students of a group in the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id, 'active' => true]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/groups/{$group->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.0.id', $student->id);
});

test('returns a group schedule', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $schedule = makeActiveSchedule($group);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/groups/{$group->id}/schedule", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.0.schedule_id', $schedule->id);
});
