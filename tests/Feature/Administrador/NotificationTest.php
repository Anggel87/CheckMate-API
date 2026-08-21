<?php

use App\Models\AppNotification;
use App\Models\Tutor;
use App\Models\User;

/**
 * Creates a student with one tutor that receives notifications and has every
 * preference enabled (Tutor::booted() already creates the preference row).
 *
 * @return array{student: User, tutor: Tutor}
 */
function makeStudentWithTutor(?int $groupId = null): array
{
    $student = User::factory()->student()->create(['group_id' => $groupId]);
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    return ['student' => $student, 'tutor' => $tutor];
}

test('lists notifications filtering by type and is_read', function () {
    ['student' => $student, 'tutor' => $tutor] = makeStudentWithTutor();
    AppNotification::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'title' => 'Aviso', 'message' => 'msj', 'type' => 'AVISO', 'is_read' => false, 'sent_at' => now()]);
    AppNotification::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'title' => 'Falta', 'message' => 'msj', 'type' => 'INASISTENCIA', 'is_read' => true, 'sent_at' => now()]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/notifications?type=AVISO', ['Authorization' => "Bearer {$token}"]);
    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'AVISO');

    $response = $this->getJson('/api/v1/administrador/notifications?is_read=1', ['Authorization' => "Bearer {$token}"]);
    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'INASISTENCIA');
});

test('shows sent_by as null for automatic notifications', function () {
    ['student' => $student, 'tutor' => $tutor] = makeStudentWithTutor();
    $notification = AppNotification::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'title' => 'Falta', 'message' => 'msj', 'type' => 'INASISTENCIA', 'is_read' => false, 'sent_at' => now()]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/notifications/{$notification->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.sent_by', null);
});

test('creates a notification targeting a student and respects announcement preferences', function () {
    $student = User::factory()->student()->create();
    $tutorA = Tutor::factory()->create();
    $tutorB = Tutor::factory()->create();
    $tutorB->notificationPreference->update(['announcements' => false]);
    $student->tutors()->attach($tutorA->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $student->tutors()->attach($tutorB->id, ['relationship' => 'Padre', 'is_primary' => false, 'receives_notifications' => true]);
    $admin = makeAdmin();
    $token = fakeGovernanceAuth($admin);

    $response = $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Junta de padres',
        'message' => 'La junta será el viernes.',
        'type' => 'AVISO',
        'target' => 'STUDENT',
        'student_ids' => [$student->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);

    $this->assertDatabaseHas('notifications', [
        'student_id' => $student->id,
        'tutor_id' => $tutorA->id,
        'type' => 'AVISO',
        'sent_by_user_id' => $admin->id,
    ]);
    $this->assertDatabaseMissing('notifications', ['tutor_id' => $tutorB->id]);
});

test('creates a notification targeting a group and fans out to every student', function () {
    $group = makeActiveGroup();
    makeStudentWithTutor($group->id);
    makeStudentWithTutor($group->id);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Aviso de grupo',
        'message' => 'Mensaje para el grupo.',
        'type' => 'AVISO',
        'target' => 'GROUP',
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 2);
});

test('creates a notification targeting a career and fans out to every student', function () {
    $group = makeActiveGroup();
    makeStudentWithTutor($group->id);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Aviso de carrera',
        'message' => 'Mensaje para la carrera.',
        'type' => 'AVISO',
        'target' => 'CAREER',
        'career_ids' => [$group->career_id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);
});

test('creates an in-app notification targeting a student directly', function () {
    $student = User::factory()->student()->create();
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $admin = makeAdmin();
    $token = fakeGovernanceAuth($admin);

    $response = $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Junta de padres',
        'message' => 'La junta será el viernes.',
        'type' => 'AVISO',
        'target' => 'STUDENT',
        'student_ids' => [$student->id],
        'recipient_type' => 'STUDENT',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $student->id,
        'student_id' => $student->id,
        'tutor_id' => null,
        'recipient_type' => 'STUDENT',
        'sent_by_user_id' => $admin->id,
    ]);
    $this->assertDatabaseMissing('notifications', ['tutor_id' => $tutor->id]);
});

test('creates an in-app notification targeting teachers', function () {
    $teacher = User::factory()->teacher()->create(['active' => true]);
    $admin = makeAdmin();
    $token = fakeGovernanceAuth($admin);

    $response = $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Junta docente',
        'message' => 'La junta será el lunes.',
        'type' => 'AVISO',
        'target' => 'TEACHER',
        'teacher_ids' => [$teacher->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $teacher->id,
        'student_id' => null,
        'tutor_id' => null,
        'recipient_type' => 'TEACHER',
        'sent_by_user_id' => $admin->id,
    ]);
});

test('rejects a notification with no valid recipients', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Aviso',
        'message' => 'Mensaje.',
        'type' => 'AVISO',
        'target' => 'STUDENT',
        'student_ids' => [999999],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'NOT02');
});

test('rejects an invalid notification payload', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/notifications', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('resends a notification to the same student', function () {
    ['student' => $student, 'tutor' => $tutor] = makeStudentWithTutor();
    $original = AppNotification::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'title' => 'Aviso original', 'message' => 'msj', 'type' => 'AVISO', 'is_read' => false, 'sent_at' => now()]);
    $admin = makeAdmin();
    $token = fakeGovernanceAuth($admin);

    $response = $this->postJson("/api/v1/administrador/notifications/{$original->id}/resend", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.original_notification_id', $original->id)
        ->assertJsonPath('data.recipients_count', 1);

    $this->assertDatabaseHas('notifications', [
        'student_id' => $student->id,
        'tutor_id' => $tutor->id,
        'title' => 'Aviso original',
        'sent_by_user_id' => $admin->id,
    ]);
});

test('resends a notification to a new target', function () {
    ['student' => $originalStudent, 'tutor' => $originalTutor] = makeStudentWithTutor();
    $original = AppNotification::create(['student_id' => $originalStudent->id, 'tutor_id' => $originalTutor->id, 'title' => 'Aviso original', 'message' => 'msj', 'type' => 'AVISO', 'is_read' => false, 'sent_at' => now()]);
    ['student' => $newStudent] = makeStudentWithTutor();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/notifications/{$original->id}/resend", [
        'target' => 'STUDENT',
        'student_ids' => [$newStudent->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);
    $this->assertDatabaseHas('notifications', ['student_id' => $newStudent->id, 'title' => 'Aviso original']);
});

test('returns not found for a missing notification', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson('/api/v1/administrador/notifications/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'NOT01');
});

test('marks a notification as read', function () {
    ['student' => $student, 'tutor' => $tutor] = makeStudentWithTutor();
    $notification = AppNotification::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'title' => 'Aviso', 'message' => 'msj', 'type' => 'AVISO', 'is_read' => false, 'sent_at' => now()]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/notifications/{$notification->id}/read", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.is_read', true);
    $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => true]);
});

test('marks every row of a batch as read when the notification was sent to several recipients', function () {
    $group = makeActiveGroup();
    makeStudentWithTutor($group->id);
    makeStudentWithTutor($group->id);
    $token = fakeGovernanceAuth(makeAdmin());

    $this->postJson('/api/v1/administrador/notifications', [
        'title' => 'Aviso de grupo',
        'message' => 'Mensaje para el grupo.',
        'type' => 'AVISO',
        'target' => 'GROUP',
        'group_ids' => [$group->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $list = $this->getJson('/api/v1/administrador/notifications', ['Authorization' => "Bearer {$token}"]);
    $representativeId = $list->json('data.0.id');

    $this->patchJson("/api/v1/administrador/notifications/{$representativeId}/read", [], ['Authorization' => "Bearer {$token}"])
        ->assertOk();

    $batchId = AppNotification::find($representativeId)->batch_id;
    expect(AppNotification::where('batch_id', $batchId)->where('is_read', false)->count())->toBe(0);
});
