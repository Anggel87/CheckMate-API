<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('lists only the authenticated student justifications', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2, 'group_id' => $group->id]);

    $mine = Justification::factory()->create([
        'attendance_id' => Attendance::factory()->absent()->create(['student_id' => $student->id]),
    ]);
    Justification::factory()->create([
        'attendance_id' => Attendance::factory()->absent()->create(['student_id' => $otherStudent->id]),
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/justifications', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);
});

test('rejects viewing another student justification', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $otherStudent = User::factory()->student()->create(['governance_user_id' => 2, 'group_id' => $group->id]);

    $justification = Justification::factory()->create([
        'attendance_id' => Attendance::factory()->absent()->create(['student_id' => $otherStudent->id]),
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/justifications/{$justification->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('creates a justification for an absent attendance record', function () {
    Storage::fake('public');

    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create([
        'student_id' => $student->id,
        'schedule_id' => $schedule->id,
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->post(
        "/api/v1/alumno/subjects/{$subject->id}/attendance/{$attendance->id}/justify",
        [
            'reason' => 'Cita médica el día de la falta.',
            'evidence' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ],
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertCreated()
        ->assertJsonPath('data.status', 'PENDIENTE')
        ->assertJsonPath('data.subject.id', $subject->id);

    $this->assertDatabaseHas('justifications', [
        'attendance_id' => $attendance->id,
        'justified_by_user_id' => $student->id,
        'status' => 'PENDIENTE',
    ]);
});

test('rejects justifying an attendance that is not absent', function () {
    Storage::fake('public');

    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->present()->create([
        'student_id' => $student->id,
        'schedule_id' => $schedule->id,
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->post(
        "/api/v1/alumno/subjects/{$subject->id}/attendance/{$attendance->id}/justify",
        [
            'reason' => 'Intento justificar una asistencia presente.',
            'evidence' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ],
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertStatus(409)->assertJsonPath('error_code', 'ATT04');
});

test('rejects justifying an attendance that already has a justification', function () {
    Storage::fake('public');

    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    $attendance = Attendance::factory()->absent()->create([
        'student_id' => $student->id,
        'schedule_id' => $schedule->id,
    ]);

    Justification::factory()->create(['attendance_id' => $attendance->id]);

    $token = fakeGovernanceAuth($student);

    $response = $this->post(
        "/api/v1/alumno/subjects/{$subject->id}/attendance/{$attendance->id}/justify",
        [
            'reason' => 'Ya tiene un justificante este registro.',
            'evidence' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ],
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertStatus(409)->assertJsonPath('error_code', 'JUST03');
});

test('rejects justifying an attendance that does not exist', function () {
    Storage::fake('public');

    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->post(
        "/api/v1/alumno/subjects/{$subject->id}/attendance/999999/justify",
        [
            'reason' => 'Este registro de asistencia no existe.',
            'evidence' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ],
        ['Authorization' => "Bearer {$token}"]
    );

    $response->assertNotFound()->assertJsonPath('error_code', 'ATT03');
});
