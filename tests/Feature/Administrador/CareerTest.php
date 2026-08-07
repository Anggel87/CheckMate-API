<?php

use App\Models\Career;
use App\Models\Group;
use App\Models\User;

test('lists only active careers by default', function () {
    Career::factory()->create();
    Career::factory()->inactive()->create();

    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/careers', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('creates a career with a valid director', function () {
    $director = User::factory()->careerDirector()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/careers', [
        'name' => 'Ingeniería en Software',
        'code' => 'ISW-01',
        'director_id' => $director->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.code', 'ISW-01');
    $this->assertDatabaseHas('careers', ['code' => 'ISW-01', 'director_id' => $director->id]);
});

test('rejects a director that does not have the career_director role', function () {
    $notADirector = User::factory()->student()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/careers', [
        'name' => 'Ingeniería en Software',
        'code' => 'ISW-02',
        'director_id' => $notADirector->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR03');
});

test('rejects a duplicate career code', function () {
    $existing = Career::factory()->create();
    $director = User::factory()->careerDirector()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/careers', [
        'name' => 'Otra carrera',
        'code' => $existing->code,
        'director_id' => $director->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CAR02');
});

test('rejects an invalid payload', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/careers', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('updates a career', function () {
    $career = Career::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/careers/{$career->id}", [
        'name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.name', 'Nombre actualizado');
});

test('deactivates a career when confirmed', function () {
    $career = Career::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/careers/{$career->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('careers', ['id' => $career->id, 'is_active' => false]);
});

test('requires confirmation to deactivate a career', function () {
    $career = Career::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/careers/{$career->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
});

test('blocks deactivating a career with active groups', function () {
    $career = Career::factory()->create();
    Group::factory()->create(['career_id' => $career->id, 'is_active' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/careers/{$career->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CAR03');
});

test('rejects a non administrator role', function () {
    $token = fakeGovernanceAuth(User::factory()->student()->create(['governance_user_id' => 2]));

    $response = $this->getJson('/api/v1/administrador/careers', ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'AUTH02');
});
