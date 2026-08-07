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
