<?php

use App\Models\Classroom;
use App\Models\Device;
use App\Models\Schedule;

test('lists classrooms ordered by building and name', function () {
    Classroom::factory()->create(['name' => 'Aula 101', 'building' => 'Edificio A']);
    Classroom::factory()->create(['name' => 'Aula 201', 'building' => 'Edificio B']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/classrooms', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('creates a classroom', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/classrooms', [
        'name' => 'Aula 301',
        'building' => 'Edificio C',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.name', 'Aula 301');
    $this->assertDatabaseHas('classroom', ['name' => 'Aula 301', 'building' => 'Edificio C']);
});

test('rejects a duplicate classroom in the same building', function () {
    $existing = Classroom::factory()->create(['name' => 'Aula 101', 'building' => 'Edificio A']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/classrooms', [
        'name' => $existing->name,
        'building' => $existing->building,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLS02');
});

test('allows the same classroom name in a different building', function () {
    Classroom::factory()->create(['name' => 'Aula 101', 'building' => 'Edificio A']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/classrooms', [
        'name' => 'Aula 101',
        'building' => 'Edificio B',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
});

test('rejects an invalid payload', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/classrooms', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('updates a classroom', function () {
    $classroom = Classroom::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/classrooms/{$classroom->id}", [
        'name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.name', 'Nombre actualizado');
});

test('rejects updating into a duplicate name and building', function () {
    $first = Classroom::factory()->create(['name' => 'Aula 101', 'building' => 'Edificio A']);
    $second = Classroom::factory()->create(['name' => 'Aula 102', 'building' => 'Edificio A']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/classrooms/{$second->id}", [
        'name' => $first->name,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLS02');
});

test('deletes an unused classroom when confirmed', function () {
    $classroom = Classroom::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/classrooms/{$classroom->id}?confirm=true", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseMissing('classroom', ['id' => $classroom->id]);
});

test('requires confirmation to delete a classroom', function () {
    $classroom = Classroom::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/classrooms/{$classroom->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
    $this->assertDatabaseHas('classroom', ['id' => $classroom->id]);
});

test('rejects deleting a classroom that has devices assigned', function () {
    $classroom = Classroom::factory()->create();
    Device::factory()->create(['classroom_id' => $classroom->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/classrooms/{$classroom->id}?confirm=true", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLS03');
    $this->assertDatabaseHas('classroom', ['id' => $classroom->id]);
});

test('rejects deleting a classroom that has schedules assigned', function () {
    $group = makeActiveGroup();
    $schedule = makeActiveSchedule($group);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/classrooms/{$schedule->classroom_id}?confirm=true", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLS03');
});

test('returns 404 for a classroom that does not exist', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/classrooms/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'CLS01');
});
