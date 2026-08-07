<?php

use App\Models\Schedule;
use App\Models\Subject;

test('creates a subject', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/subjects', [
        'name' => 'Base de Datos',
        'code' => 'BD-101',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.code', 'BD-101');
});

test('rejects a duplicate subject code', function () {
    $existing = Subject::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/subjects', [
        'name' => 'Otra materia',
        'code' => $existing->code,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SUBJ02');
});

test('rejects an invalid payload', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/subjects', ['code' => 'x'], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('updates a subject', function () {
    $subject = Subject::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/subjects/{$subject->id}", [
        'name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.name', 'Nombre actualizado');
});

test('deactivates a subject when confirmed', function () {
    $subject = Subject::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/subjects/{$subject->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'is_active' => false]);
});

test('requires confirmation to deactivate a subject', function () {
    $subject = Subject::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/subjects/{$subject->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
});

test('blocks deactivating a subject with active schedules', function () {
    $subject = Subject::factory()->create();
    Schedule::factory()->create(['subject_id' => $subject->id, 'is_active' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/subjects/{$subject->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SUBJ03');
});
