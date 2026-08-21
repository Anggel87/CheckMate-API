<?php

use App\Mail\TemporaryPasswordMail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function baseUserPayload(): array
{
    return [
        'first_name' => 'Juan',
        'first_surname' => 'Ramírez',
        'second_surname' => 'Torres',
        'email' => 'juan.ramirez@example.com',
        'phone' => '8711234567',
        'birth_date' => '2006-05-10',
        'gender' => 'M',
    ];
}

test('creates a student without any tutor', function () {
    Mail::fake();
    $group = makeActiveGroup();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'alumno',
        'group_id' => $group->id,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'juan.ramirez@example.com')
        ->assertJsonCount(0, 'data.tutors');

    $this->assertDatabaseHas('users', ['email' => 'juan.ramirez@example.com', 'group_id' => $group->id]);
});

test('creates a student with multiple tutors and an nfc uid in one call', function () {
    Mail::fake();
    $group = makeActiveGroup();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'alumno',
        'group_id' => $group->id,
        'nfc_uid' => 'AA:11:22:33',
        'tutors' => [
            ['first_name' => 'María', 'first_surname' => 'Torres', 'second_surname' => 'López', 'phone' => '8719876543', 'relationship' => 'Madre', 'is_primary' => true],
            ['first_name' => 'Pedro', 'first_surname' => 'Torres', 'second_surname' => 'López', 'phone' => '8719876544', 'relationship' => 'Padre'],
        ],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonCount(2, 'data.tutors');

    $student = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    $this->assertDatabaseHas('student_tutor', ['student_id' => $student->id, 'is_primary' => true]);
    $this->assertDatabaseHas('user_details', ['user_id' => $student->id, 'nfc_uid' => 'AA:11:22:33']);
});

test('creates a teacher through the generic endpoint', function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'profesor']);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'profesor',
        'nfc_uid' => 'BB:11:22:33',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    $teacher = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    expect($teacher->role->name)->toBe('profesor');
    $this->assertDatabaseHas('user_details', ['user_id' => $teacher->id, 'nfc_uid' => 'BB:11:22:33']);
});

test('creates a tutor academico through the generic endpoint', function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'tutor_academico']);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'tutor_academico',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    $teacher = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    expect($teacher->role->name)->toBe('tutor_academico');
    $this->assertDatabaseHas('academic_tutors', ['user_id' => $teacher->id, 'is_active' => true]);
});

test('creates a director_carrera account, which had no creation flow before', function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'director_carrera']);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'director_carrera',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.role', 'director_carrera');
    $director = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    expect($director->role->name)->toBe('director_carrera');

    Mail::assertSent(TemporaryPasswordMail::class, fn (TemporaryPasswordMail $mail) => $mail->hasTo($director->email));
});

test('creates an administrador account', function () {
    Mail::fake();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'administrador',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonPath('data.role', 'administrador');
});

test('ignores an nfc uid for director_carrera and administrador roles', function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'director_carrera']);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'director_carrera',
        'nfc_uid' => 'CC:11:22:33',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    $director = User::where('email', 'juan.ramirez@example.com')->firstOrFail();
    expect(UserDetail::where('user_id', $director->id)->exists())->toBeFalse();
});

test('rejects an nfc uid already assigned to another user', function () {
    Mail::fake();
    $group = makeActiveGroup();
    $other = User::factory()->student()->create(['group_id' => $group->id]);
    UserDetail::create(['user_id' => $other->id, 'nfc_uid' => 'DD:11:22:33', 'qr_uuid' => (string) Str::uuid()]);
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'alumno',
        'group_id' => $group->id,
        'nfc_uid' => 'DD:11:22:33',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'NFC01');
});

test('rejects a missing role', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/users', baseUserPayload(), ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('rejects a student without a group_id', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'role' => 'alumno',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('rejects a duplicate email regardless of role', function () {
    $existing = User::factory()->administrator()->create();
    $token = fakeGovernanceAuth(makeAdmin());
    fakeGovernanceCreateUser();

    $response = $this->postJson('/api/v1/administrador/users', [
        ...baseUserPayload(),
        'email' => $existing->email,
        'role' => 'administrador',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'USR04');
});
