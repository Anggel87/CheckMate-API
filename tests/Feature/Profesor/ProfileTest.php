<?php

use App\Models\User;

test('returns the authenticated teacher profile', function () {
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 7]);
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/profile', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $teacher->id)
        ->assertJsonPath('data.role', 'profesor');
});

test('academic tutor role can also see their own profile', function () {
    $tutor = User::factory()->academicTutor()->create(['governance_user_id' => 8]);
    $token = fakeGovernanceAuth($tutor);

    $this->getJson('/api/v1/profesor/profile', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.role', 'tutor_academico');
});

test('teacher updates their own phone', function () {
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 7]);
    $token = fakeGovernanceAuth($teacher);

    $this->putJson('/api/v1/profesor/profile', [
        'phone' => '5599998888',
    ], ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.phone', '5599998888');

    $this->assertDatabaseHas('users', ['id' => $teacher->id, 'phone' => '5599998888']);
});

test('rejects a user whose role is neither profesor nor tutor_academico', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 9]);
    $token = fakeGovernanceAuth($student);

    $this->getJson('/api/v1/profesor/profile', ['Authorization' => "Bearer {$token}"])
        ->assertForbidden();
});
