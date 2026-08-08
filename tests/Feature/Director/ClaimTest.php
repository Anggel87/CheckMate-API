<?php

use App\Models\Attendance;
use App\Models\Claim;

test('lists claims addressed to the director only', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $schedule = makeActiveSchedule($group);
    $attendance = Attendance::factory()->create(['schedule_id' => $schedule->id]);
    $mine = Claim::factory()->create(['attendance_id' => $attendance->id, 'director_id' => $director->id]);

    Claim::factory()->create();

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/claims', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('acts on a pending claim addressed to the director', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $schedule = makeActiveSchedule($group);
    $attendance = Attendance::factory()->create(['schedule_id' => $schedule->id]);
    $claim = Claim::factory()->create(['attendance_id' => $attendance->id, 'director_id' => $director->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->patchJson("/api/v1/director-carrera/claims/{$claim->id}/action", [
        'action' => 'CONTACTADO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'CONTACTADO');
    $this->assertDatabaseHas('claims', ['id' => $claim->id, 'status' => 'CONTACTADO', 'action_by_user_id' => $director->id]);
});

test('returns 404 for a claim addressed to another director', function () {
    ['director' => $director] = makeCareerDirector();
    $claim = Claim::factory()->create();

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson("/api/v1/director-carrera/claims/{$claim->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'CLM01');
});
