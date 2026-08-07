<?php

use App\Models\SchoolYear;

test('lists school years filtered by status', function () {
    SchoolYear::factory()->create(['status' => 'ACTIVO']);
    SchoolYear::factory()->create(['status' => 'FINALIZADO']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/school-years?status=ACTIVO', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('creates a school year defaulting to PROXIMO', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/school-years', [
        'name' => '2027-2028',
        'start_date' => '2027-08-01',
        'end_date' => '2028-06-30',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.status', 'PROXIMO');
});

test('rejects a duplicate school year name', function () {
    $existing = SchoolYear::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/school-years', [
        'name' => $existing->name,
        'start_date' => '2027-08-01',
        'end_date' => '2028-06-30',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SY02');
});

test('rejects an end date before the start date', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/school-years', [
        'name' => '2027-2028',
        'start_date' => '2027-08-01',
        'end_date' => '2027-01-01',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('activating a school year finishes the previously active one', function () {
    $active = SchoolYear::factory()->create(['status' => 'ACTIVO']);
    $upcoming = SchoolYear::factory()->create(['status' => 'PROXIMO']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/school-years/{$upcoming->id}", [
        'status' => 'ACTIVO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'ACTIVO');
    $this->assertDatabaseHas('school_years', ['id' => $active->id, 'status' => 'FINALIZADO']);
});

test('returns not found for a missing school year', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/school-years/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SY01');
});
