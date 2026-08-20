<?php

use App\Models\Attendance;
use App\Models\Claim;

test('lists claims from every director, unlike the director-scoped endpoint', function () {
    ['director' => $directorA, 'group' => $groupA] = makeCareerDirector(1);
    ['director' => $directorB, 'group' => $groupB] = makeCareerDirector(2);

    $attendanceA = Attendance::factory()->create(['schedule_id' => makeActiveSchedule($groupA)->id]);
    $attendanceB = Attendance::factory()->create(['schedule_id' => makeActiveSchedule($groupB)->id]);

    $claimA = Claim::factory()->create(['attendance_id' => $attendanceA->id, 'director_id' => $directorA->id]);
    $claimB = Claim::factory()->create(['attendance_id' => $attendanceB->id, 'director_id' => $directorB->id]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/claims', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$claimA->id, $claimB->id])->sort()->values()->all());
});

test('acts on any claim regardless of which director it was addressed to', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector(1);
    $attendance = Attendance::factory()->create(['schedule_id' => makeActiveSchedule($group)->id]);
    $claim = Claim::factory()->create(['attendance_id' => $attendance->id, 'director_id' => $director->id]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->patchJson("/api/v1/administrador/claims/{$claim->id}/action", [
        'action' => 'CONTACTADO',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.status', 'CONTACTADO');
});

test('returns 404 for a nonexistent claim', function () {
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/claims/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'CLM01');
});
