<?php

use App\Models\Tutor;
use App\Models\User;

test('adds a secondary tutor to a student', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $primary = Tutor::factory()->create();
    $student->tutors()->attach($primary->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/students/{$student->id}/tutors", [
        'first_name' => 'Carlos',
        'first_surname' => 'Pérez',
        'second_surname' => 'Gómez',
        'phone' => '8711112222',
        'relationship' => 'Padre',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonCount(2, 'data.tutors');
});

test('reassigns the primary tutor when adding a new one as primary', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $primary = Tutor::factory()->create();
    $student->tutors()->attach($primary->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $this->postJson("/api/v1/administrador/students/{$student->id}/tutors", [
        'first_name' => 'Carlos',
        'first_surname' => 'Pérez',
        'second_surname' => 'Gómez',
        'phone' => '8711112222',
        'relationship' => 'Padre',
        'is_primary' => true,
    ], ['Authorization' => "Bearer {$token}"]);

    $this->assertDatabaseHas('student_tutor', ['student_id' => $student->id, 'tutor_id' => $primary->id, 'is_primary' => false]);
});

test('updates a tutor and its pivot fields', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/students/{$student->id}/tutors/{$tutor->id}", [
        'phone' => '8719998888',
        'receives_notifications' => false,
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('tutors', ['id' => $tutor->id, 'phone' => '8719998888']);
    $this->assertDatabaseHas('student_tutor', ['student_id' => $student->id, 'tutor_id' => $tutor->id, 'receives_notifications' => false]);
});

test('rejects updating a tutor that is not assigned to the student', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $unrelatedTutor = Tutor::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->putJson("/api/v1/administrador/students/{$student->id}/tutors/{$unrelatedTutor->id}", [
        'phone' => '8719998888',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'TUT01');
});

test('blocks removing the only tutor of a student', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/students/{$student->id}/tutors/{$tutor->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'TUT02');
});

test('removes a secondary tutor', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $primary = Tutor::factory()->create();
    $secondary = Tutor::factory()->create();
    $student->tutors()->attach($primary->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $student->tutors()->attach($secondary->id, ['relationship' => 'Padre', 'is_primary' => false, 'receives_notifications' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/students/{$student->id}/tutors/{$secondary->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseMissing('student_tutor', ['student_id' => $student->id, 'tutor_id' => $secondary->id]);
});
