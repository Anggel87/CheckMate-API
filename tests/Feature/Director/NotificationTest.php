<?php

use App\Models\AppNotification;
use App\Models\User;

test('creates a notification for a student within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Aviso de carrera',
        'message' => 'Mensaje para el alumno.',
        'type' => 'AVISO',
        'target' => 'STUDENT',
        'student_ids' => [$student->id],
        'recipient_type' => 'STUDENT',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $student->id,
        'recipient_type' => 'STUDENT',
        'sent_by_user_id' => $director->id,
    ]);
});

test('creates a notification for every student in the director career without submitting ids', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    User::factory()->student()->create(['group_id' => $group->id]);
    User::factory()->student()->create(['group_id' => $group->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Aviso de carrera',
        'message' => 'Mensaje para toda la carrera.',
        'type' => 'AVISO',
        'target' => 'CAREER',
        'recipient_type' => 'STUDENT',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 2);
});

test('creates a notification for a teacher within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['teacher' => $teacher] = makeTeacherWithSchedule($group, null, 2);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Junta docente',
        'message' => 'Mensaje para el profesor.',
        'type' => 'AVISO',
        'target' => 'TEACHER',
        'teacher_ids' => [$teacher->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $teacher->id,
        'recipient_type' => 'TEACHER',
        'sent_by_user_id' => $director->id,
    ]);
});

test('rejects targeting a student outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();
    $otherStudent = User::factory()->student()->create(['group_id' => $otherGroup->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Aviso',
        'message' => 'Mensaje.',
        'type' => 'AVISO',
        'target' => 'STUDENT',
        'student_ids' => [$otherStudent->id],
        'recipient_type' => 'STUDENT',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('rejects targeting a group outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Aviso',
        'message' => 'Mensaje.',
        'type' => 'AVISO',
        'target' => 'GROUP',
        'group_ids' => [$otherGroup->id],
        'recipient_type' => 'STUDENT',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('rejects targeting a teacher outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    ['teacher' => $otherTeacher] = makeTeacherWithSchedule(null, null, 2);

    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Aviso',
        'message' => 'Mensaje.',
        'type' => 'AVISO',
        'target' => 'TEACHER',
        'teacher_ids' => [$otherTeacher->id],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('rejects a target of ALL, which is not available to directors', function () {
    ['director' => $director] = makeCareerDirector();
    $token = fakeGovernanceAuth($director);

    $response = $this->postJson('/api/v1/director-carrera/notifications', [
        'title' => 'Aviso',
        'message' => 'Mensaje.',
        'type' => 'AVISO',
        'target' => 'ALL',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('lists only notifications sent by the authenticated director', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['director' => $otherDirector] = makeCareerDirector(2);
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $mine = AppNotification::create([
        'user_id' => $student->id,
        'student_id' => $student->id,
        'recipient_type' => 'STUDENT',
        'sent_by_user_id' => $director->id,
        'title' => 'Mio',
        'message' => 'msj',
        'type' => 'AVISO',
        'is_read' => false,
        'sent_at' => now(),
    ]);
    AppNotification::create([
        'user_id' => $student->id,
        'student_id' => $student->id,
        'recipient_type' => 'STUDENT',
        'sent_by_user_id' => $otherDirector->id,
        'title' => 'Ajeno',
        'message' => 'msj',
        'type' => 'AVISO',
        'is_read' => false,
        'sent_at' => now(),
    ]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/notifications', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('marks a notification sent by the director as read', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $mine = AppNotification::create([
        'user_id' => $student->id,
        'student_id' => $student->id,
        'recipient_type' => 'STUDENT',
        'sent_by_user_id' => $director->id,
        'title' => 'Mio',
        'message' => 'msj',
        'type' => 'AVISO',
        'is_read' => false,
        'sent_at' => now(),
    ]);

    $token = fakeGovernanceAuth($director);

    $response = $this->patchJson("/api/v1/director-carrera/notifications/{$mine->id}/read", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.is_read', true);
    $this->assertDatabaseHas('notifications', ['id' => $mine->id, 'is_read' => true]);
});

test('rejects marking as read a notification sent by another director', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    ['director' => $otherDirector] = makeCareerDirector(2);
    $student = User::factory()->student()->create(['group_id' => $group->id]);

    $theirs = AppNotification::create([
        'user_id' => $student->id,
        'student_id' => $student->id,
        'recipient_type' => 'STUDENT',
        'sent_by_user_id' => $otherDirector->id,
        'title' => 'Ajeno',
        'message' => 'msj',
        'type' => 'AVISO',
        'is_read' => false,
        'sent_at' => now(),
    ]);

    $token = fakeGovernanceAuth($director);

    $response = $this->patchJson("/api/v1/director-carrera/notifications/{$theirs->id}/read", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'NOT01');
});
