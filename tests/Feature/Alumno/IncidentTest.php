<?php

use App\Models\Incident;
use App\Models\User;

test('returns null when there is no active incident', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/incidents/active', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data', null);
});

test('reports the active incident and whether the student already marked themselves safe', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $incident = Incident::factory()->create(['title' => 'Sismo', 'description' => 'Favor de evacuar de inmediato.']);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/incidents/active', ['Authorization' => "Bearer {$token}"]);

    // title/description son necesarios para el modal global de alerta de incidentes
    // (IncidentAlertService), que en alumno consume este mismo endpoint directamente
    // en lugar de un GET /incidents/{id} aparte, a diferencia de los demas roles.
    $response->assertOk()
        ->assertJsonPath('data.id', $incident->id)
        ->assertJsonPath('data.title', 'Sismo')
        ->assertJsonPath('data.description', 'Favor de evacuar de inmediato.')
        ->assertJsonPath('data.already_reported', false);
});

test('a student can report themselves safe during an active incident', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $incident = Incident::factory()->create();

    $token = fakeGovernanceAuth($student);

    $response = $this->postJson("/api/v1/alumno/incidents/{$incident->id}/report-safe", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'SEGURO');
    $this->assertDatabaseHas('incident_students', [
        'incident_id' => $incident->id,
        'student_id' => $student->id,
        'status' => 'SEGURO',
        'checked_by_user_id' => $student->id,
    ]);

    $activeResponse = $this->getJson('/api/v1/alumno/incidents/active', ['Authorization' => "Bearer {$token}"]);
    $activeResponse->assertOk()->assertJsonPath('data.already_reported', true);
});

test('rejects reporting safe when there is no active incident with that id', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $incident = Incident::factory()->resolved()->create();

    $token = fakeGovernanceAuth($student);

    $response = $this->postJson("/api/v1/alumno/incidents/{$incident->id}/report-safe", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'INC05');
});
