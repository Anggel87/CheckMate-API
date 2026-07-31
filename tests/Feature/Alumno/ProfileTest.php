<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('returns the authenticated student profile', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create([
        'governance_user_id' => 42,
        'group_id' => $group->id,
    ]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/profile', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.group.id', $group->id)
        ->assertJsonPath('data.career.id', $group->career_id);
});

test('rejects requests without a bearer token', function () {
    $response = $this->getJson('/api/v1/alumno/profile');

    $response->assertUnauthorized()
        ->assertJsonPath('error_code', 'AUTH05');
});

test('rejects a valid governance token with no local user linked', function () {
    $student = User::factory()->student()->make(['governance_user_id' => 999]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/profile', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertForbidden();
});

test('caches the governance /auth/me lookup between requests with the same token', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create([
        'governance_user_id' => 42,
        'group_id' => $group->id,
    ]);

    $token = fakeGovernanceAuth($student);

    $this->getJson('/api/v1/alumno/profile', ['Authorization' => "Bearer {$token}"])->assertOk();
    $this->getJson('/api/v1/alumno/profile', ['Authorization' => "Bearer {$token}"])->assertOk();

    Http::assertSentCount(1);
});

test('rejects a user whose role is not alumno', function () {
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 7]);
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/alumno/profile', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertForbidden()
        ->assertJsonPath('error_code', 'AUTH02');
});
