<?php

use App\Mail\TemporaryPasswordMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('creates a teacher', function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'profesor']);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/teachers', [
        'first_name' => 'Carlos',
        'first_surname' => 'López',
        'second_surname' => 'Pérez',
        'email' => 'carlos.lopez@example.com',
        'phone' => '8711234567',
        'birth_date' => '1985-05-10',
        'gender' => 'M',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'carlos.lopez@example.com')
        ->assertJsonPath('data.is_academic_tutor', false)
        ->assertJsonPath('data.temporary_password', 'Temp1234!');

    $teacher = User::where('email', 'carlos.lopez@example.com')->firstOrFail();
    $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    expect($teacher->role->name)->toBe('profesor');
    $this->assertDatabaseHas('audit_logs', ['entity' => 'teacher', 'entity_id' => $teacher->id, 'action' => 'CREATE']);

    Mail::assertSent(TemporaryPasswordMail::class, fn (TemporaryPasswordMail $mail) => $mail->hasTo($teacher->email)
        && $mail->temporaryPassword === 'Temp1234!');
});

test('creates a teacher as academic tutor directly', function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'tutor_academico']);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/teachers', [
        'first_name' => 'Carlos',
        'first_surname' => 'López',
        'second_surname' => 'Pérez',
        'email' => 'carlos.lopez@example.com',
        'phone' => '8711234567',
        'birth_date' => '1985-05-10',
        'gender' => 'M',
        'is_academic_tutor' => true,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.is_academic_tutor', true);

    $teacher = User::where('email', 'carlos.lopez@example.com')->firstOrFail();
    expect($teacher->role->name)->toBe('tutor_academico');
    $this->assertDatabaseHas('academic_tutors', ['user_id' => $teacher->id, 'is_active' => true]);
});

test('rejects a duplicate teacher email', function () {
    $existing = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/teachers', [
        'first_name' => 'Carlos',
        'first_surname' => 'López',
        'second_surname' => 'Pérez',
        'email' => $existing->email,
        'phone' => '8711234567',
        'birth_date' => '1985-05-10',
        'gender' => 'M',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'USR04');
});

test('rejects an invalid payload', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/teachers', [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('lists teachers filtering by is_academic_tutor and active', function () {
    $token = fakeGovernanceAuth(makeAdmin());
    User::factory()->teacher()->create(['first_name' => 'Profesor Regular']);
    User::factory()->academicTutor()->create(['first_name' => 'Tutor Academico']);
    User::factory()->teacher()->inactive()->create(['first_name' => 'Profesor Inactivo']);

    $response = $this->getJson('/api/v1/administrador/teachers?is_academic_tutor=1', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.first_name'))->toBe('Tutor Academico');

    $response = $this->getJson('/api/v1/administrador/teachers?active=0', ['Authorization' => "Bearer {$token}"]);
    $response->assertOk()->assertJsonCount(1, 'data');
});

test('shows a teacher with schedules_count and tutored_groups', function () {
    $token = fakeGovernanceAuth(makeAdmin());
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group, 2);

    $response = $this->getJson("/api/v1/administrador/teachers/{$tutor->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.is_academic_tutor', true)
        ->assertJsonCount(1, 'data.tutored_groups');
});

test('updates a teacher', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/teachers/{$teacher->id}", [
        'first_name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.first_name', 'Nombre actualizado');
    $this->assertDatabaseHas('audit_logs', ['entity' => 'teacher', 'entity_id' => $teacher->id, 'action' => 'UPDATE']);
});

test('rejects updating a teacher email to one already used by another user', function () {
    $existing = User::factory()->teacher()->create();
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/teachers/{$teacher->id}", [
        'email' => $existing->email,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'USR04');
});

test('allows updating a teacher keeping its own email', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/teachers/{$teacher->id}", [
        'email' => $teacher->email,
        'first_name' => 'Nombre actualizado',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.first_name', 'Nombre actualizado');
});

test('deactivates a teacher when confirmed', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/teachers/{$teacher->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('users', ['id' => $teacher->id, 'active' => false]);
    $this->assertDatabaseHas('audit_logs', ['entity' => 'teacher', 'entity_id' => $teacher->id, 'action' => 'DELETE']);
});

test('requires confirmation to deactivate a teacher', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/teachers/{$teacher->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
});

test('rejects deactivating a teacher with active schedules', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule(governanceUserId: 2);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/teachers/{$teacher->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'USR05');
});
