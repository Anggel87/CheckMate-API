<?php

use App\Models\AppNotification;
use App\Models\Incident;
use App\Models\User;

test('creates an incident anchored to any schedule, unlike the career-scoped director endpoint', function () {
    $group = makeActiveGroup();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/incidents', [
        'type' => 'FIRE',
        'title' => 'Conato de incendio',
        'severity' => 'ALTA',
        'schedule_id' => $schedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.title', 'Conato de incendio');
    $this->assertDatabaseHas('incidents', ['schedule_id' => $schedule->id]);
});

test('lists incidents from every group in the school', function () {
    ['schedule' => $scheduleA] = makeTeacherWithSchedule(makeActiveGroup(), null, 2);
    ['schedule' => $scheduleB] = makeTeacherWithSchedule(makeActiveGroup(), null, 3);

    $incidentA = Incident::factory()->create(['schedule_id' => $scheduleA->id]);
    $incidentB = Incident::factory()->create(['schedule_id' => $scheduleB->id]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/incidents', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$incidentA->id, $incidentB->id])->sort()->values()->all());
});

test('closes an active incident', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule(makeActiveGroup(), null, 2);
    $incident = Incident::factory()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson("/api/v1/administrador/incidents/{$incident->id}/close", [
        'resolution' => 'RESOLVED',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'RESUELTO');
});

test('rejects closing an already closed incident', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule(makeActiveGroup(), null, 2);
    $incident = Incident::factory()->resolved()->create(['schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson("/api/v1/administrador/incidents/{$incident->id}/close", [
        'resolution' => 'CANCELLED',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'INC03');
});

test('updates the emergency roster status of an incident', function () {
    $group = makeActiveGroup();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $incident = Incident::factory()->create(['schedule_id' => $schedule->id]);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $incident->students()->attach($student->id, ['status' => 'DESCONOCIDO', 'checked_by_user_id' => makeAdmin(998)->id, 'checked_at' => now()]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->patchJson("/api/v1/administrador/incidents/{$incident->id}/students", [
        'students' => [['student_id' => $student->id, 'status' => 'SEGURO']],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.updated_count', 1);
    $this->assertDatabaseHas('incident_students', ['incident_id' => $incident->id, 'student_id' => $student->id, 'status' => 'SEGURO']);
});

test('returns 404 for a nonexistent incident', function () {
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/incidents/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'INC01');
});

test('rejects creating an incident while another one is active', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule(makeActiveGroup(), null, 2);
    Incident::factory()->create(['schedule_id' => $schedule->id, 'status' => 'ACTIVO']);
    $student = User::factory()->student()->create();

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/incidents', [
        'type' => 'GAS',
        'title' => 'Fuga de gas',
        'severity' => 'ALTA',
        'schedule_id' => $schedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'INC04');
});

test('allows creating a new incident once the active one is closed', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule(makeActiveGroup(), null, 2);
    $closed = Incident::factory()->resolved()->create(['schedule_id' => $schedule->id]);
    $student = User::factory()->student()->create();

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/incidents', [
        'type' => 'GAS',
        'title' => 'Fuga de gas',
        'severity' => 'ALTA',
        'schedule_id' => $schedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    expect(Incident::where('status', 'ACTIVO')->count())->toBe(1);
    expect($closed->fresh()->status)->toBe('RESUELTO');
});

test('notifies the whole school when an incident is reported', function () {
    $group = makeActiveGroup();
    ['schedule' => $schedule, 'teacher' => $reportingTeacher] = makeTeacherWithSchedule($group, null, 2);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    ['teacher' => $otherTeacher] = makeTeacherWithSchedule(makeActiveGroup(), null, 3);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/incidents', [
        'type' => 'EARTHQUAKE',
        'title' => 'Sismo',
        'severity' => 'CRITICA',
        'schedule_id' => $schedule->id,
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();

    // Alumno: WhatsApp al tutor (si tiene) + aviso en la app.
    $this->assertDatabaseHas('notifications', [
        'user_id' => $student->id,
        'recipient_type' => 'STUDENT',
        'type' => 'INCIDENTE',
        'title' => 'Sismo',
    ]);

    // Ambos profesores (no solo el que reporto ni solo los de su grupo) reciben el aviso.
    $this->assertDatabaseHas('notifications', ['user_id' => $reportingTeacher->id, 'recipient_type' => 'TEACHER', 'type' => 'INCIDENTE']);
    $this->assertDatabaseHas('notifications', ['user_id' => $otherTeacher->id, 'recipient_type' => 'TEACHER', 'type' => 'INCIDENTE']);

    // Todas las filas del aviso comparten un solo batch_id (una sola entrada en el log).
    $batchIds = AppNotification::where('type', 'INCIDENTE')->pluck('batch_id')->unique();
    expect($batchIds)->toHaveCount(1);
});
