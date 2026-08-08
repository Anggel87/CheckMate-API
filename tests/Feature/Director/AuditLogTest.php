<?php

use App\Models\AuditLog;
use App\Models\User;

test('lists only audit logs for students within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $admin = makeAdmin(2);

    $mine = AuditLog::create([
        'entity' => 'student',
        'entity_id' => $student->id,
        'action' => 'UPDATE',
        'performed_by_user_id' => $admin->id,
        'before' => ['active' => true],
        'after' => ['active' => false],
    ]);

    $otherGroup = makeActiveGroup();
    $otherStudent = User::factory()->student()->create(['group_id' => $otherGroup->id]);
    AuditLog::create([
        'entity' => 'student',
        'entity_id' => $otherStudent->id,
        'action' => 'UPDATE',
        'performed_by_user_id' => $admin->id,
        'before' => null,
        'after' => null,
    ]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/logs/students', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('shows a single audit log within the director career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $admin = makeAdmin(2);

    $log = AuditLog::create([
        'entity' => 'student',
        'entity_id' => $student->id,
        'action' => 'CREATE',
        'performed_by_user_id' => $admin->id,
        'before' => null,
        'after' => ['active' => true],
    ]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/logs/{$log->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.id', $log->id)->assertJsonPath('data.performed_by.id', $admin->id);
});

test('returns 404 for an audit log outside the director career', function () {
    ['director' => $director] = makeCareerDirector();
    $otherGroup = makeActiveGroup();
    $otherStudent = User::factory()->student()->create(['group_id' => $otherGroup->id]);
    $admin = makeAdmin(2);

    $log = AuditLog::create([
        'entity' => 'student',
        'entity_id' => $otherStudent->id,
        'action' => 'CREATE',
        'performed_by_user_id' => $admin->id,
        'before' => null,
        'after' => null,
    ]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/logs/{$log->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'LOG01');
});
