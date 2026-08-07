<?php

use App\Models\Career;
use App\Models\Group;
use App\Models\SchoolYear;
use App\Models\User;

test('creates a group', function () {
    $schoolYear = SchoolYear::factory()->create();
    $career = Career::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/groups', [
        'school_year_id' => $schoolYear->id,
        'career_id' => $career->id,
        'grade' => '1',
        'section' => 'A',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.grade', '1');
});

test('rejects a group referencing a missing school year', function () {
    $career = Career::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/groups', [
        'school_year_id' => 999999,
        'career_id' => $career->id,
        'grade' => '1',
        'section' => 'A',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SY01');
});

test('rejects a duplicate grade and section within the same career and school year', function () {
    $schoolYear = SchoolYear::factory()->create();
    $career = Career::factory()->create();
    Group::factory()->create([
        'school_year_id' => $schoolYear->id,
        'career_id' => $career->id,
        'grade' => '1',
        'section' => 'A',
    ]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/groups', [
        'school_year_id' => $schoolYear->id,
        'career_id' => $career->id,
        'grade' => '1',
        'section' => 'A',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'GRP03');
});

test('requires confirmation to deactivate a group', function () {
    $group = Group::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/groups/{$group->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
});

test('blocks deactivating a group with active students', function () {
    $group = Group::factory()->create();
    User::factory()->student()->create(['group_id' => $group->id, 'active' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/groups/{$group->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'GRP04');
});

test('deactivates a group with no active students when confirmed', function () {
    $group = Group::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/groups/{$group->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('groups', ['id' => $group->id, 'is_active' => false]);
});
