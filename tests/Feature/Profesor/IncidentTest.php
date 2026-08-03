<?php

use App\Models\Incident;
use App\Models\User;

test('creates an incident and seeds the emergency checklist for the given groups', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson('/api/v1/profesor/incidents', [
        'type' => 'FIRE',
        'title' => 'Conato de incendio en laboratorio',
        'severity' => 'ALTA',
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'ACTIVO')
        ->assertJsonPath('data.students.0.id', $student->id);

    $this->assertDatabaseHas('incident_students', ['student_id' => $student->id, 'status' => 'DESCONOCIDO']);
});

test('lists only incidents reported by the teacher', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $mine = Incident::factory()->create(['reported_by_user_id' => $teacher->id, 'schedule_id' => $schedule->id]);
    Incident::factory()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/incidents', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('lists all active incidents regardless of reporter', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    Incident::factory()->create(['schedule_id' => $schedule->id, 'status' => 'ACTIVO']);
    Incident::factory()->create(['schedule_id' => $schedule->id, 'status' => 'RESUELTO']);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/incidents/active', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data');
});

test('rejects updating an incident reported by another teacher', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $incident = Incident::factory()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->putJson("/api/v1/profesor/incidents/{$incident->id}", [
        'title' => 'Intento de edición ajena',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('rejects updating a closed incident', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $incident = Incident::factory()->create([
        'reported_by_user_id' => $teacher->id,
        'schedule_id' => $schedule->id,
        'status' => 'RESUELTO',
    ]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->putJson("/api/v1/profesor/incidents/{$incident->id}", [
        'title' => 'Ya no se puede editar',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'INC02');
});

test('updates the emergency checklist marking students present or absent', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $incident = Incident::factory()->create(['reported_by_user_id' => $teacher->id, 'schedule_id' => $schedule->id]);
    $incident->students()->attach($student->id, ['status' => 'DESCONOCIDO', 'checked_by_user_id' => $teacher->id, 'checked_at' => now()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->patchJson("/api/v1/profesor/incidents/{$incident->id}/students", [
        'students' => [['student_id' => $student->id, 'present' => true]],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.present_count', 1);
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incident->id, 'student_id' => $student->id, 'status' => 'PRESENTE']);
});

test('returns 404 for a nonexistent incident', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/incidents/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'INC01');
});
