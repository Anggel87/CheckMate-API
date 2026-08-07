<?php

use App\Models\AcademicTutor;
use App\Models\Role;
use App\Models\User;

test('activates academic tutor without groups', function () {
    Role::firstOrCreate(['name' => 'tutor_academico']);
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/teachers/{$teacher->id}/academic-tutor", [
        'is_active' => true,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.is_academic_tutor', true)
        ->assertJsonCount(0, 'data.groups');

    $this->assertDatabaseHas('academic_tutors', ['user_id' => $teacher->id, 'is_active' => true]);
    expect($teacher->fresh()->role->name)->toBe('tutor_academico');
});

test('activates academic tutor with groups and syncs the pivot', function () {
    Role::firstOrCreate(['name' => 'tutor_academico']);
    $teacher = User::factory()->teacher()->create();
    $group = makeActiveGroup();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/teachers/{$teacher->id}/academic-tutor", [
        'is_active' => true,
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data.groups');

    $academicTutor = AcademicTutor::where('user_id', $teacher->id)->firstOrFail();
    $this->assertDatabaseHas('group_academic_tutor', [
        'group_id' => $group->id,
        'academic_tutor_id' => $academicTutor->id,
        'is_active' => true,
    ]);
});

test('deactivates academic tutor and reverts the role', function () {
    Role::firstOrCreate(['name' => 'profesor']);
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group, 2);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/teachers/{$tutor->id}/academic-tutor", [
        'is_active' => false,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.is_academic_tutor', false);

    expect($tutor->fresh()->role->name)->toBe('profesor');
    $this->assertDatabaseHas('academic_tutors', ['user_id' => $tutor->id, 'is_active' => false]);
});

test('rejects assigning a missing group', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/teachers/{$teacher->id}/academic-tutor", [
        'is_active' => true,
        'group_ids' => [999999],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'GRP02');
});

test('returns not found for a missing teacher', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson('/api/v1/administrador/teachers/999999/academic-tutor', [
        'is_active' => true,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR01');
});
