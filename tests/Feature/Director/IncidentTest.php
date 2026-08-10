<?php

use App\Models\Incident;
use App\Models\User;

test('creates an incident within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/incidents', [
        'type' => 'FIRE',
        'title' => 'Conato de incendio',
        'severity' => 'ALTA',
        'schedule_id' => $schedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.title', 'Conato de incendio');
    $this->assertDatabaseHas('incidents', ['schedule_id' => $schedule->id, 'reviewed_by_user_id' => $director->id]);
});

test('records a history trail including the closing resolution', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $token = fakeGovernanceAuth($director);

    $created = $this->postJson('/api/v1/director-carrera/incidents', [
        'type' => 'FIRE',
        'title' => 'Conato de incendio',
        'severity' => 'ALTA',
        'schedule_id' => $schedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $incidentId = $created->json('data.id');

    $this->patchJson("/api/v1/director-carrera/incidents/{$incidentId}/students", [
        'students' => [['student_id' => $student->id, 'status' => 'SEGURO']],
    ], ['Authorization' => "Bearer {$token}"]);

    $this->postJson("/api/v1/director-carrera/incidents/{$incidentId}/close", [
        'resolution' => 'RESOLVED',
    ], ['Authorization' => "Bearer {$token}"]);

    $response = $this->getJson("/api/v1/director-carrera/incidents/{$incidentId}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(3, 'data.history');
    expect($response->json('data.history.1.after.status'))->toBe('SEGURO');
    expect($response->json('data.history.2.after.status'))->toBe('RESUELTO');
});

test('rejects an incident for a schedule outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule($otherGroup, null, 2);
    $student = User::factory()->student()->create(['group_id' => $otherGroup->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/incidents', [
        'type' => 'FIRE',
        'title' => 'Conato de incendio',
        'severity' => 'ALTA',
        'schedule_id' => $otherSchedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('closes an active incident', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $incident = Incident::factory()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson("/api/v1/director-carrera/incidents/{$incident->id}/close", [
        'resolution' => 'RESOLVED',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'RESUELTO');
    $this->assertDatabaseHas('incidents', ['id' => $incident->id, 'status' => 'RESUELTO', 'reviewed_by_user_id' => $director->id]);
});

test('rejects closing an already closed incident', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $incident = Incident::factory()->resolved()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson("/api/v1/director-carrera/incidents/{$incident->id}/close", [
        'resolution' => 'CANCELLED',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'INC03');
});

test('updates the emergency roster status of an incident', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $incident = Incident::factory()->create(['schedule_id' => $schedule->id]);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $incident->students()->attach($student->id, ['status' => 'DESCONOCIDO', 'checked_by_user_id' => $director->id, 'checked_at' => now()]);

    $token = fakeGovernanceAuth($director);

    $response = $this->patchJson("/api/v1/director-carrera/incidents/{$incident->id}/students", [
        'students' => [['student_id' => $student->id, 'status' => 'SEGURO']],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.updated_count', 1);
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incident->id, 'student_id' => $student->id, 'status' => 'SEGURO']);
});
