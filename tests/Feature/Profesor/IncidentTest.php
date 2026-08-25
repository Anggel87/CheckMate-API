<?php

use App\Models\Incident;
use App\Models\Tutor;
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

test('creates an incident without title, severity or groups and applies a generic title', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson('/api/v1/profesor/incidents', [
        'type' => 'FIRE',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'ACTIVO')
        ->assertJsonPath('data.severity', null)
        ->assertJsonCount(0, 'data.students');

    expect($response->json('data.title'))->not->toBeEmpty();
});

test('accepts a blank title and severity when editing an incident created without them', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();

    $token = fakeGovernanceAuth($teacher);

    $created = $this->postJson('/api/v1/profesor/incidents', [
        'type' => 'FIRE',
    ], ['Authorization' => "Bearer {$token}"]);

    $incidentId = $created->json('data.id');

    $response = $this->putJson("/api/v1/profesor/incidents/{$incidentId}", [
        'title' => '',
        'description' => '',
        'severity' => '',
        'type' => 'FIRE',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.title', null)
        ->assertJsonPath('data.severity', null);
});

test('adds groups to an existing incident on edit without duplicating already checked students', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $created = $this->postJson('/api/v1/profesor/incidents', [
        'type' => 'FIRE',
    ], ['Authorization' => "Bearer {$token}"]);

    $incidentId = $created->json('data.id');

    $first = $this->putJson("/api/v1/profesor/incidents/{$incidentId}", [
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $first->assertOk()->assertJsonCount(1, 'data.students');

    $second = $this->putJson("/api/v1/profesor/incidents/{$incidentId}", [
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $second->assertOk()->assertJsonCount(1, 'data.students');
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incidentId, 'student_id' => $student->id, 'status' => 'DESCONOCIDO']);
});

test('records a history trail as an incident is created, edited and its checklist updated', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $created = $this->postJson('/api/v1/profesor/incidents', [
        'type' => 'FIRE',
        'title' => 'Conato de incendio',
        'severity' => 'ALTA',
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $incidentId = $created->json('data.id');

    $this->putJson("/api/v1/profesor/incidents/{$incidentId}", [
        'title' => 'Conato de incendio controlado',
    ], ['Authorization' => "Bearer {$token}"]);

    $this->patchJson("/api/v1/profesor/incidents/{$incidentId}/students", [
        'students' => [['student_id' => $student->id, 'status' => 'PRESENTE']],
    ], ['Authorization' => "Bearer {$token}"]);

    $response = $this->getJson("/api/v1/profesor/incidents/{$incidentId}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(3, 'data.history');
    expect($response->json('data.history.0.action'))->toBe('CREATE');
    expect($response->json('data.history.1.after.title'))->toBe('Conato de incendio controlado');
    expect($response->json('data.history.2.after.status'))->toBe('PRESENTE');
});

test('rejects creating an incident while another one is active, even one reported by another role', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    Incident::factory()->create(['schedule_id' => $schedule->id, 'status' => 'ACTIVO']);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson('/api/v1/profesor/incidents', [
        'type' => 'FIRE',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'INC04');
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
        'students' => [['student_id' => $student->id, 'status' => 'PRESENTE']],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.present_count', 1);
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incident->id, 'student_id' => $student->id, 'status' => 'PRESENTE']);
});

test('the teacher can mark a student safe directly when they have no phone to self-report', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $incident = Incident::factory()->create(['reported_by_user_id' => $teacher->id, 'schedule_id' => $schedule->id]);
    $incident->students()->attach($student->id, ['status' => 'DESCONOCIDO', 'checked_by_user_id' => $teacher->id, 'checked_at' => now()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->patchJson("/api/v1/profesor/incidents/{$incident->id}/students", [
        'students' => [['student_id' => $student->id, 'status' => 'SEGURO']],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.safe_count', 1);
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incident->id, 'student_id' => $student->id, 'status' => 'SEGURO']);
});

test('a student already marked safe cannot be reverted to another status', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $incident = Incident::factory()->create(['reported_by_user_id' => $teacher->id, 'schedule_id' => $schedule->id]);
    $incident->students()->attach($student->id, ['status' => 'SEGURO', 'checked_by_user_id' => $student->id, 'checked_at' => now()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->patchJson("/api/v1/profesor/incidents/{$incident->id}/students", [
        'students' => [['student_id' => $student->id, 'status' => 'AUSENTE']],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.updated_students', 0);
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incident->id, 'student_id' => $student->id, 'status' => 'SEGURO']);
});

test('notifies tutors of students who have not reported their status', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $unreportedStudent = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    $reportedStudent = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $tutor = Tutor::factory()->create();
    $unreportedStudent->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    $incident = Incident::factory()->create(['reported_by_user_id' => $teacher->id, 'schedule_id' => $schedule->id]);
    $incident->students()->attach($unreportedStudent->id, ['status' => 'DESCONOCIDO', 'checked_by_user_id' => $teacher->id, 'checked_at' => now()]);
    $incident->students()->attach($reportedStudent->id, ['status' => 'SEGURO', 'checked_by_user_id' => $reportedStudent->id, 'checked_at' => now()]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/incidents/{$incident->id}/notify-unreported", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.notified_count', 1);
    $this->assertDatabaseHas('notifications', [
        'tutor_id' => $tutor->id,
        'student_id' => $unreportedStudent->id,
        'type' => 'INCIDENTE',
    ]);
});

test('rejects notifying unreported students for an incident reported by another teacher', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $incident = Incident::factory()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/incidents/{$incident->id}/notify-unreported", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 for a nonexistent incident', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/incidents/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'INC01');
});
