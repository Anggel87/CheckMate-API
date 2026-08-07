<?php

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermissionOverride;

/**
 * Creates a Permission granted to $role via a fresh PermissionGroup, mirroring
 * what PermissionSeeder does in production for the "{role}.full" groups.
 */
function grantRolePermission(Role $role, string $keyName): Permission
{
    $permission = Permission::create(['name' => $keyName, 'key_name' => $keyName, 'is_active' => true]);
    $group = PermissionGroup::create(['name' => "{$role->name}.{$keyName}", 'key_name' => "{$role->name}.{$keyName}", 'is_active' => true]);
    $group->permissions()->attach($permission->id);
    $role->permissionGroups()->attach($group->id);

    return $permission;
}

test('shows role permissions, overrides and effective permissions', function () {
    $teacher = User::factory()->teacher()->create();
    grantRolePermission($teacher->role, 'profesor.groups.view');
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/users/{$teacher->id}/permissions", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.user_id', $teacher->id)
        ->assertJsonCount(1, 'data.role_permissions')
        ->assertJsonPath('data.role_permissions.0.key_name', 'profesor.groups.view')
        ->assertJsonCount(0, 'data.overrides');

    expect($response->json('data.effective_permissions'))->toContain('profesor.groups.view');
});

test('creates an ALLOW override and adds it to effective permissions', function () {
    $teacher = User::factory()->teacher()->create();
    $permission = Permission::create(['name' => 'special.action', 'key_name' => 'special.action', 'is_active' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/users/{$teacher->id}/permissions/override", [
        'permission_id' => $permission->id,
        'type' => 'PERMITIR',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()->assertJsonCount(1, 'data.overrides');
    expect($response->json('data.effective_permissions'))->toContain('special.action');

    $this->assertDatabaseHas('user_permission_overrides', [
        'users_id' => $teacher->id,
        'permissions_id' => $permission->id,
        'type' => 'PERMITIR',
    ]);
});

test('creates a DENY override and removes it from effective permissions', function () {
    $teacher = User::factory()->teacher()->create();
    $permission = grantRolePermission($teacher->role, 'profesor.groups.view');
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/users/{$teacher->id}/permissions/override", [
        'permission_id' => $permission->id,
        'type' => 'DENEGAR',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
    expect($response->json('data.effective_permissions'))->not->toContain('profesor.groups.view');
});

test('rejects a duplicate override', function () {
    $teacher = User::factory()->teacher()->create();
    $permission = Permission::create(['name' => 'special.action', 'key_name' => 'special.action', 'is_active' => true]);
    UserPermissionOverride::create(['users_id' => $teacher->id, 'permissions_id' => $permission->id, 'type' => 'PERMITIR']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/users/{$teacher->id}/permissions/override", [
        'permission_id' => $permission->id,
        'type' => 'PERMITIR',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'PERM04');
});

test('rejects an override for a missing permission', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/users/{$teacher->id}/permissions/override", [
        'permission_id' => 999999,
        'type' => 'PERMITIR',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'PERM03');
});

test('rejects an override for a missing user', function () {
    $permission = Permission::create(['name' => 'special.action', 'key_name' => 'special.action', 'is_active' => true]);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson('/api/v1/administrador/users/999999/permissions/override', [
        'permission_id' => $permission->id,
        'type' => 'PERMITIR',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR01');
});

test('rejects an invalid override payload', function () {
    $teacher = User::factory()->teacher()->create();
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->postJson("/api/v1/administrador/users/{$teacher->id}/permissions/override", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL01');
});

test('deletes an override and removes it from effective permissions', function () {
    $teacher = User::factory()->teacher()->create();
    $permission = Permission::create(['name' => 'special.action', 'key_name' => 'special.action', 'is_active' => true]);
    $override = UserPermissionOverride::create(['users_id' => $teacher->id, 'permissions_id' => $permission->id, 'type' => 'PERMITIR']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/users/{$teacher->id}/permissions/override/{$override->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(0, 'data.overrides');
    expect($response->json('data.effective_permissions'))->not->toContain('special.action');
    $this->assertDatabaseMissing('user_permission_overrides', ['id' => $override->id]);
});

test('rejects deleting a missing or foreign override', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    $permission = Permission::create(['name' => 'special.action', 'key_name' => 'special.action', 'is_active' => true]);
    $override = UserPermissionOverride::create(['users_id' => $otherTeacher->id, 'permissions_id' => $permission->id, 'type' => 'PERMITIR']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->deleteJson("/api/v1/administrador/users/{$teacher->id}/permissions/override/{$override->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'PERM05');
});

test('lists users filtering by role_id and has_overrides', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $permission = Permission::create(['name' => 'special.action', 'key_name' => 'special.action', 'is_active' => true]);
    UserPermissionOverride::create(['users_id' => $teacher->id, 'permissions_id' => $permission->id, 'type' => 'PERMITIR']);
    $token = fakeGovernanceAuth(makeAdmin());

    $response = $this->getJson("/api/v1/administrador/users/permissions?role_id={$teacher->role_id}", ['Authorization' => "Bearer {$token}"]);
    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $teacher->id);

    $response = $this->getJson('/api/v1/administrador/users/permissions?has_overrides=1', ['Authorization' => "Bearer {$token}"]);
    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.overrides_count', 1);
});
