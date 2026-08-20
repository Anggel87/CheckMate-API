<?php

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\Subject;
use App\Models\User;

test('lists only the authenticated student claims', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2, 'group_id' => $group->id]);

    $mine = Claim::factory()->create(['tutor_id' => $student->id]);
    Claim::factory()->create(['tutor_id' => $otherStudent->id]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/claims', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);
});

test('rejects viewing another student claim', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2, 'group_id' => $group->id]);

    $claim = Claim::factory()->create(['tutor_id' => $otherStudent->id]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/claims/{$claim->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('creates a claim for a subject the student is enrolled in', function () {
    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    Attendance::factory()->absent()->create([
        'student_id' => $student->id,
        'schedule_id' => $schedule->id,
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->postJson('/api/v1/alumno/claims', [
        'subject_id' => $subject->id,
        'description' => 'No se registró correctamente mi asistencia de hoy.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.subject.id', $subject->id)
        ->assertJsonPath('data.status', 'PENDIENTE');

    $this->assertDatabaseHas('claims', [
        'tutor_id' => $student->id,
        'description' => 'No se registró correctamente mi asistencia de hoy.',
    ]);
});

test('rejects creating a claim for a subject the student is not enrolled in', function () {
    $group = makeActiveGroup();
    $otherSubject = Subject::factory()->create();

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->postJson('/api/v1/alumno/claims', [
        'subject_id' => $otherSubject->id,
        'description' => 'No curso esta materia y aun asi reclamo.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM02');
});

test('validates required fields when creating a claim', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->postJson('/api/v1/alumno/claims', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable()
        ->assertJsonPath('error_code', 'VAL01')
        ->assertJsonValidationErrors(['description']);
});

test('creates a general claim without a subject', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->postJson('/api/v1/alumno/claims', [
        'description' => 'El baño de la planta baja no sirve desde hace una semana.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.subject', null)
        ->assertJsonPath('data.status', 'PENDIENTE');

    $this->assertDatabaseHas('claims', [
        'tutor_id' => $student->id,
        'attendance_id' => null,
        'director_id' => $group->career->director_id,
        'description' => 'El baño de la planta baja no sirve desde hace una semana.',
    ]);
});

test('rejects creating a general claim when the student has no group', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => null]);
    $token = fakeGovernanceAuth($student);

    $response = $this->postJson('/api/v1/alumno/claims', [
        'description' => 'Reclamo general sin grupo asignado.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409)->assertJsonPath('error_code', 'GRP05');
});

test('updates a pending claim', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $student->id, 'status' => 'PENDIENTE', 'description' => 'Descripcion original del reclamo.']);

    $token = fakeGovernanceAuth($student);

    $response = $this->putJson("/api/v1/alumno/claims/{$claim->id}", [
        'description' => 'Descripcion corregida del reclamo con mas detalle.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.description', 'Descripcion corregida del reclamo con mas detalle.');

    $this->assertDatabaseHas('claims', [
        'id' => $claim->id,
        'description' => 'Descripcion corregida del reclamo con mas detalle.',
    ]);
});

test('rejects updating another student claim', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $otherStudent->id, 'status' => 'PENDIENTE']);

    $token = fakeGovernanceAuth($student);

    $response = $this->putJson("/api/v1/alumno/claims/{$claim->id}", [
        'description' => 'Intento de editar un reclamo ajeno.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('rejects updating a claim that is already being attended', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $student->id, 'status' => 'EN_PROCESO']);

    $token = fakeGovernanceAuth($student);

    $response = $this->putJson("/api/v1/alumno/claims/{$claim->id}", [
        'description' => 'Ya no deberia poder editar esto.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLM03');
});

test('cancels a pending claim when confirmed', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $student->id, 'status' => 'PENDIENTE']);

    $token = fakeGovernanceAuth($student);

    $response = $this->deleteJson("/api/v1/alumno/claims/{$claim->id}?confirm=true", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseMissing('claims', ['id' => $claim->id]);
});

test('requires confirmation to cancel a claim', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $student->id, 'status' => 'PENDIENTE']);

    $token = fakeGovernanceAuth($student);

    $response = $this->deleteJson("/api/v1/alumno/claims/{$claim->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
    $this->assertDatabaseHas('claims', ['id' => $claim->id]);
});

test('rejects cancelling another student claim', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $otherStudent->id, 'status' => 'PENDIENTE']);

    $token = fakeGovernanceAuth($student);

    $response = $this->deleteJson("/api/v1/alumno/claims/{$claim->id}?confirm=true", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
    $this->assertDatabaseHas('claims', ['id' => $claim->id]);
});

test('rejects cancelling a claim that is already being attended', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $claim = Claim::factory()->create(['tutor_id' => $student->id, 'status' => 'EN_PROCESO']);

    $token = fakeGovernanceAuth($student);

    $response = $this->deleteJson("/api/v1/alumno/claims/{$claim->id}?confirm=true", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLM03');
    $this->assertDatabaseHas('claims', ['id' => $claim->id]);
});
