<?php

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Str;

test('lists users with their current nfc uid, or null if they have none', function () {
    $student = User::factory()->student()->create();
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => '04:AA:BB:CC', 'qr_uuid' => (string) Str::uuid()]);
    $withoutCard = User::factory()->student()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/nfc-cards?search={$student->first_name}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.0.nfc_uid', '04:AA:BB:CC');

    $response = $this->getJson("/api/v1/administrador/nfc-cards?search={$withoutCard->first_name}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.0.nfc_uid', null);
});

test('assigns a new nfc uid to a user who has no user_details row yet', function () {
    $student = User::factory()->student()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/nfc-cards/{$student->id}", [
        'nfc_uid' => '04:12:34:56',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.nfc_uid', '04:12:34:56');

    $this->assertDatabaseHas('user_details', ['user_id' => $student->id, 'nfc_uid' => '04:12:34:56']);
    expect(UserDetail::where('user_id', $student->id)->first()->qr_uuid)->not->toBeNull();
});

test('replaces the nfc uid of a user who already has a card assigned', function () {
    $teacher = User::factory()->teacher()->create();
    UserDetail::create(['user_id' => $teacher->id, 'nfc_uid' => 'OLD-UID', 'qr_uuid' => (string) Str::uuid()]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/nfc-cards/{$teacher->id}", [
        'nfc_uid' => 'NEW-UID',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.nfc_uid', 'NEW-UID');
    $this->assertDatabaseHas('user_details', ['user_id' => $teacher->id, 'nfc_uid' => 'NEW-UID']);
    $this->assertDatabaseMissing('user_details', ['user_id' => $teacher->id, 'nfc_uid' => 'OLD-UID']);
});

test('rejects assigning a nfc uid that already belongs to another user', function () {
    $student = User::factory()->student()->create();
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => 'TAKEN-UID', 'qr_uuid' => (string) Str::uuid()]);
    $otherStudent = User::factory()->student()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/nfc-cards/{$otherStudent->id}", [
        'nfc_uid' => 'TAKEN-UID',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(409)->assertJsonPath('error_code', 'NFC01');
});

test('rejects an invalid nfc uid format', function () {
    $student = User::factory()->student()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson("/api/v1/administrador/nfc-cards/{$student->id}", [
        'nfc_uid' => 'not valid!!',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('clears a nfc uid without deleting the user_details row', function () {
    $student = User::factory()->student()->create();
    UserDetail::create(['user_id' => $student->id, 'nfc_uid' => '04:AA:BB:CC', 'qr_uuid' => 'keep-me']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/nfc-cards/{$student->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.nfc_uid', null);
    $this->assertDatabaseHas('user_details', ['user_id' => $student->id, 'nfc_uid' => null, 'qr_uuid' => 'keep-me']);
});

test('returns not found when assigning a nfc uid to a missing user', function () {
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->patchJson('/api/v1/administrador/nfc-cards/999999', [
        'nfc_uid' => '04:12:34:56',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR01');
});
