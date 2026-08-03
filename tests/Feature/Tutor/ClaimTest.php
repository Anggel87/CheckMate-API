<?php

use App\Models\Attendance;
use App\Models\Claim;

test('lists claims from the tutor active groups only', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);
    $schedule = makeActiveSchedule($group);

    $attendance = Attendance::factory()->create(['schedule_id' => $schedule->id]);
    $mine = Claim::factory()->create(['attendance_id' => $attendance->id]);

    $otherGroup = makeActiveGroup();
    $otherSchedule = makeActiveSchedule($otherGroup);
    $otherAttendance = Attendance::factory()->create(['schedule_id' => $otherSchedule->id]);
    Claim::factory()->create(['attendance_id' => $otherAttendance->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->getJson('/api/v1/tutor/claims', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('acts on a pending claim', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);
    $schedule = makeActiveSchedule($group);

    $attendance = Attendance::factory()->create(['schedule_id' => $schedule->id]);
    $claim = Claim::factory()->create(['attendance_id' => $attendance->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->patchJson("/api/v1/tutor/claims/{$claim->id}/action", [
        'action' => 'EN_PROCESO',
        'comment' => 'Contactando al alumno.',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'EN_PROCESO');
    $this->assertDatabaseHas('claims', ['id' => $claim->id, 'status' => 'EN_PROCESO', 'action_by_user_id' => $tutor->id]);
});

test('rejects acting on an already resolved claim', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group);
    $schedule = makeActiveSchedule($group);

    $attendance = Attendance::factory()->create(['schedule_id' => $schedule->id]);
    $claim = Claim::factory()->create(['attendance_id' => $attendance->id, 'status' => 'ACEPTADO']);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->patchJson("/api/v1/tutor/claims/{$claim->id}/action", [
        'action' => 'RECHAZADO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'CLM02');
});
