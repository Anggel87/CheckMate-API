<?php

use App\Models\Attendance;
use App\Models\Incident;
use App\Models\Justification;
use App\Models\User;

test('returns a school-wide attendance summary across every group', function () {
    $groupA = makeActiveGroup();
    $groupB = makeActiveGroup();
    $studentA = User::factory()->student()->create(['group_id' => $groupA->id]);
    $studentB = User::factory()->student()->create(['group_id' => $groupB->id]);

    Attendance::factory()->create(['student_id' => $studentA->id, 'schedule_id' => makeActiveSchedule($groupA)->id, 'status' => 'PRESENTE']);
    Attendance::factory()->create(['student_id' => $studentB->id, 'schedule_id' => makeActiveSchedule($groupB)->id, 'status' => 'FALTA']);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/charts/general', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.attendance_summary.PRESENTE', 1)
        ->assertJsonPath('data.attendance_summary.FALTA', 1);
});

test('returns school-wide incident statistics', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule(makeActiveGroup(), null, 2);
    Incident::factory()->create(['schedule_id' => $schedule->id, 'severity' => 'CRITICA']);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/charts/incidents', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.total', 1);
});

test('returns all chart datasets in a single summary response', function () {
    $group = makeActiveGroup();
    $schedule = makeActiveSchedule($group);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    Attendance::factory()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id, 'status' => 'PRESENTE']);
    $absence = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Justification::factory()->create(['attendance_id' => $absence->id]);
    Incident::factory()->create(['schedule_id' => $schedule->id, 'severity' => 'CRITICA']);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/charts/summary', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.general.attendance_summary.PRESENTE', 1)
        ->assertJsonPath('data.incidents.total', 1)
        ->assertJsonPath('data.absences.by_group.0.total', 1)
        ->assertJsonPath('data.justifications.by_status.PENDIENTE', 1);
});
