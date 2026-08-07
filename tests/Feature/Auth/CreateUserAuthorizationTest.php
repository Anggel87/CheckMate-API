<?php

use App\Models\User;

test('rejects creating a governance user without authentication', function () {
    $response = $this->postJson('/api/v1/auth/users', [
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo@example.com',
        'role' => 'alumno',
    ]);

    $response->assertStatus(401)->assertJsonPath('error_code', 'AUTH05');
});

test('rejects creating a governance user when the caller is not an administrator', function () {
    $token = fakeGovernanceAuth(User::factory()->student()->create(['governance_user_id' => 1]));

    $response = $this->postJson('/api/v1/auth/users', [
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo@example.com',
        'role' => 'alumno',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'AUTH02');
});
