<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Tutor;
use App\Models\User;

test('shows a student profile for a student the teacher teaches', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.group.id', $group->id);
});

test('rejects viewing a student outside the teacher groups', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $otherGroup = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 when the id does not belong to a student', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$teacher->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR01');
});

test('lists attendance only for subjects the teacher teaches the student', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $mySchedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $otherSchedule = makeActiveSchedule($group);

    Attendance::factory()->present()->create(['student_id' => $student->id, 'schedule_id' => $mySchedule->id]);
    Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $otherSchedule->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}/attendance", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'PRESENTE');
});

test('adds a legal tutor to a student the teacher teaches', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/students/{$student->id}/tutors", [
        'first_name' => 'Carlos',
        'first_surname' => 'Pérez',
        'second_surname' => 'Gómez',
        'phone' => '8711112222',
        'relationship' => 'Padre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonCount(1, 'data.tutors');
    $this->assertDatabaseHas('student_tutor', ['student_id' => $student->id, 'relationship' => 'Padre']);
});

test('rejects adding a tutor for a student outside the teacher groups', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $otherGroup = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $otherGroup->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/students/{$student->id}/tutors", [
        'first_name' => 'Carlos',
        'first_surname' => 'Pérez',
        'second_surname' => 'Gómez',
        'phone' => '8711112222',
        'relationship' => 'Padre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('sends a manual notification to a student tutors', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/students/{$student->id}/notify", [
        'title' => 'Aviso importante',
        'message' => 'El alumno se sintió mal en clase.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.recipients_count', 1);
    $this->assertDatabaseHas('notifications', [
        'student_id' => $student->id,
        'tutor_id' => $tutor->id,
        'type' => 'AVISO',
        'title' => 'Aviso importante',
    ]);
});

test('rejects notifying a student without tutors', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->postJson("/api/v1/profesor/students/{$student->id}/notify", [
        'title' => 'Aviso importante',
        'message' => 'El alumno se sintió mal en clase.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable()->assertJsonPath('error_code', 'NOT02');
});

test('lists justifications only for subjects the teacher teaches the student', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $mySchedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $mySchedule->id]);
    Justification::factory()->create(['attendance_id' => $attendance->id, 'justified_by_user_id' => $student->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/students/{$student->id}/justifications", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data');
});
