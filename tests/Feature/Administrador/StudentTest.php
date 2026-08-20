<?php

use App\Mail\TemporaryPasswordMail;
use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('creates a student with its primary tutor', function () {
    Mail::fake();
    $group = makeActiveGroup();
    $admin = makeAdmin();
    $token = fakeGovernanceAuth($admin);
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/students', [
        'first_name' => 'Juan',
        'first_surname' => 'Ramírez',
        'second_surname' => 'Torres',
        'email' => 'juan.ramirez@example.com',
        'phone' => '8711234567',
        'birth_date' => '2006-05-10',
        'gender' => 'M',
        'group_id' => $group->id,
        'tutor_first_name' => 'María',
        'tutor_first_surname' => 'Torres',
        'tutor_second_surname' => 'López',
        'tutor_phone' => '8719876543',
        'tutor_relationship' => 'Madre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'juan.ramirez@example.com')
        ->assertJsonPath('data.temporary_password', 'Temp1234!')
        ->assertJsonCount(1, 'data.tutors');

    $this->assertDatabaseHas('users', ['email' => 'juan.ramirez@example.com', 'group_id' => $group->id]);
    $this->assertDatabaseHas('tutors', ['first_name' => 'María', 'phone' => '8719876543']);

    $student = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    $tutor = $student->tutors()->first();

    $this->assertDatabaseHas('student_tutor', ['student_id' => $student->id, 'tutor_id' => $tutor->id, 'is_primary' => true]);
    $this->assertDatabaseHas('notification_preferences', ['tutor_id' => $tutor->id, 'absences' => true]);
    $this->assertDatabaseHas('audit_logs', ['entity' => 'student', 'entity_id' => $student->id, 'action' => 'CREATE']);

    Mail::assertSent(TemporaryPasswordMail::class, fn (TemporaryPasswordMail $mail) => $mail->hasTo($student->email)
        && $mail->temporaryPassword === 'Temp1234!');
});

test('rejects a duplicate student email', function () {
    $group = makeActiveGroup();
    $existing = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/students', [
        'first_name' => 'Juan',
        'first_surname' => 'Ramírez',
        'second_surname' => 'Torres',
        'email' => $existing->email,
        'phone' => '8711234567',
        'birth_date' => '2006-05-10',
        'gender' => 'M',
        'group_id' => $group->id,
        'tutor_first_name' => 'María',
        'tutor_first_surname' => 'Torres',
        'tutor_second_surname' => 'López',
        'tutor_phone' => '8719876543',
        'tutor_relationship' => 'Madre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'USR04');
});

test('rejects a student referencing a missing group', function () {
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/students', [
        'first_name' => 'Juan',
        'first_surname' => 'Ramírez',
        'second_surname' => 'Torres',
        'email' => 'juan.ramirez@example.com',
        'phone' => '8711234567',
        'birth_date' => '2006-05-10',
        'gender' => 'M',
        'group_id' => 999999,
        'tutor_first_name' => 'María',
        'tutor_first_surname' => 'Torres',
        'tutor_second_surname' => 'López',
        'tutor_phone' => '8719876543',
        'tutor_relationship' => 'Madre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'GRP02');
});

test('rejects an invalid payload', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/students', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('uploads a student photo', function () {
    Mail::fake();
    Storage::fake('public');
    $group = makeActiveGroup();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->post('/api/v1/administrador/students', [
        'first_name' => 'Juan',
        'first_surname' => 'Ramírez',
        'second_surname' => 'Torres',
        'email' => 'juan.ramirez@example.com',
        'phone' => '8711234567',
        'birth_date' => '2006-05-10',
        'gender' => 'M',
        'group_id' => $group->id,
        'photo' => UploadedFile::fake()->image('foto.jpg')->size(500),
        'tutor_first_name' => 'María',
        'tutor_first_surname' => 'Torres',
        'tutor_second_surname' => 'López',
        'tutor_phone' => '8719876543',
        'tutor_relationship' => 'Madre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    $student = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    Storage::disk('public')->assertExists($student->photo);
});

test('updates a student', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/students/{$student->id}", [
        'first_name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.first_name', 'Nombre actualizado');
    $this->assertDatabaseHas('audit_logs', ['entity' => 'student', 'entity_id' => $student->id, 'action' => 'UPDATE']);
});

test('rejects updating a student email to one already used by another user', function () {
    $group = makeActiveGroup();
    $existing = User::factory()->student()->create(['group_id' => $group->id]);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/students/{$student->id}", [
        'email' => $existing->email,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'USR04');
});

test('allows updating a student keeping its own email', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/students/{$student->id}", [
        'email' => $student->email,
        'first_name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.first_name', 'Nombre actualizado');
});

test('updates a student address', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/students/{$student->id}", [
        'address' => 'Av. Siempre Viva 742',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.address', 'Av. Siempre Viva 742');
    $this->assertDatabaseHas('users', ['id' => $student->id, 'address' => 'Av. Siempre Viva 742']);
});

test('deactivates a student when confirmed', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/students/{$student->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('users', ['id' => $student->id, 'active' => false]);
    $this->assertDatabaseHas('audit_logs', ['entity' => 'student', 'entity_id' => $student->id, 'action' => 'DELETE']);
});

test('requires confirmation to deactivate a student', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/students/{$student->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
});

test('lists a student justifications', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $schedule = makeActiveSchedule($group);
    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $justification = Justification::factory()->create(['attendance_id' => $attendance->id]);

    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/students/{$student->id}/justifications", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $justification->id);
});

test('still creates the student even if the welcome email fails to send', function () {
    Mail::shouldReceive('to')->andReturnSelf();
    Mail::shouldReceive('send')->andThrow(new Exception('SMTP no disponible'));

    $group = makeActiveGroup();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/students', [
        'first_name' => 'Juan',
        'first_surname' => 'Ramírez',
        'second_surname' => 'Torres',
        'email' => 'juan.ramirez@example.com',
        'phone' => '8711234567',
        'birth_date' => '2006-05-10',
        'gender' => 'M',
        'group_id' => $group->id,
        'tutor_first_name' => 'María',
        'tutor_first_surname' => 'Torres',
        'tutor_second_surname' => 'López',
        'tutor_phone' => '8719876543',
        'tutor_relationship' => 'Madre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    $this->assertDatabaseHas('users', ['email' => 'juan.ramirez@example.com']);
});
