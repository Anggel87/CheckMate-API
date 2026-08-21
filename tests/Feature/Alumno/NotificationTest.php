<?php

use App\Models\AppNotification;
use App\Models\User;

function makeStudentNotification(User $student, array $overrides = []): AppNotification
{
    return AppNotification::create(array_merge([
        'user_id' => $student->id,
        'student_id' => $student->id,
        'tutor_id' => null,
        'recipient_type' => 'STUDENT',
        'title' => 'Aviso',
        'message' => 'Mensaje',
        'type' => 'AVISO',
        'is_read' => false,
        'sent_at' => now(),
    ], $overrides));
}

test('lists only the authenticated student in-app notifications', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2]);

    $mine = makeStudentNotification($student);
    makeStudentNotification($otherStudent);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/notifications', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('excludes WhatsApp-to-tutor notifications from the student inbox', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1]);
    $tutor = \App\Models\Tutor::factory()->create();
    AppNotification::create([
        'student_id' => $student->id,
        'tutor_id' => $tutor->id,
        'user_id' => null,
        'recipient_type' => 'TUTOR',
        'title' => 'Falta',
        'message' => 'msj',
        'type' => 'INASISTENCIA',
        'is_read' => false,
        'sent_at' => now(),
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/notifications', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(0, 'data');
});

test('marks a notification as read', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1]);
    $notification = makeStudentNotification($student);

    $token = fakeGovernanceAuth($student);

    $response = $this->patchJson("/api/v1/alumno/notifications/{$notification->id}/read", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.is_read', true);
    $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
});

test('rejects viewing another student notification', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2]);
    $notification = makeStudentNotification($otherStudent);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/notifications/{$notification->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'NOT01');
});
