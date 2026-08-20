<?php

use App\Models\AuditLog;
use App\Models\User;

test('lists student audit logs from every group, unlike the career-scoped director endpoint', function () {
    $groupA = makeActiveGroup();
    $groupB = makeActiveGroup();
    $studentA = User::factory()->student()->create(['group_id' => $groupA->id]);
    $studentB = User::factory()->student()->create(['group_id' => $groupB->id]);
    $actor = makeAdmin(2);

    $logA = AuditLog::create([
        'entity' => 'student', 'entity_id' => $studentA->id, 'action' => 'UPDATE',
        'performed_by_user_id' => $actor->id, 'before' => null, 'after' => null,
    ]);
    $logB = AuditLog::create([
        'entity' => 'student', 'entity_id' => $studentB->id, 'action' => 'UPDATE',
        'performed_by_user_id' => $actor->id, 'before' => null, 'after' => null,
    ]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/logs/students', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$logA->id, $logB->id])->sort()->values()->all());
});

test('shows a single audit log by id', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $actor = makeAdmin(2);

    $log = AuditLog::create([
        'entity' => 'student', 'entity_id' => $student->id, 'action' => 'CREATE',
        'performed_by_user_id' => $actor->id, 'before' => null, 'after' => ['active' => true],
    ]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson("/api/v1/administrador/logs/{$log->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.id', $log->id)->assertJsonPath('data.performed_by.id', $actor->id);
});

test('returns 404 for a nonexistent audit log', function () {
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/logs/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'LOG01');
});
