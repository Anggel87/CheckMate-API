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
        ->assertJsonValidationErrors(['subject_id', 'description']);
});
