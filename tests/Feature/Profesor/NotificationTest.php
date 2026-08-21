<?php

use App\Models\AppNotification;
use App\Models\User;

function makeTeacherNotification(User $teacher, array $overrides = []): AppNotification
{
    return AppNotification::create(array_merge([
        'user_id' => $teacher->id,
        'student_id' => null,
        'tutor_id' => null,
        'recipient_type' => 'TEACHER',
        'title' => 'Aviso',
        'message' => 'Mensaje',
        'type' => 'AVISO',
        'is_read' => false,
        'sent_at' => now(),
    ], $overrides));
}

test('lists only the authenticated teacher in-app notifications', function () {
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 1]);
    $otherTeacher = User::factory()->teacher()->create(['governance_user_id' => 2]);

    $mine = makeTeacherNotification($teacher);
    makeTeacherNotification($otherTeacher);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/notifications', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('marks a teacher notification as read', function () {
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 1]);
    $notification = makeTeacherNotification($teacher);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->patchJson("/api/v1/profesor/notifications/{$notification->id}/read", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.is_read', true);
    $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
});

test('rejects viewing another teacher notification', function () {
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 1]);
    $otherTeacher = User::factory()->teacher()->create(['governance_user_id' => 2]);
    $notification = makeTeacherNotification($otherTeacher);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/notifications/{$notification->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'NOT01');
});
