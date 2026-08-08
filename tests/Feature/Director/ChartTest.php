<?php

use App\Models\Attendance;
use App\Models\Incident;
use App\Models\Justification;
use App\Models\User;

test('returns a general attendance summary scoped to the career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $schedule = makeActiveSchedule($group);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    Attendance::factory()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id, 'status' => 'PRESENTE']);
    Attendance::factory()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id, 'status' => 'FALTA']);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/charts/general', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.attendance_summary.PRESENTE', 1)
        ->assertJsonPath('data.attendance_summary.FALTA', 1);
});

test('returns incident statistics scoped to the career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $schedule = makeActiveSchedule($group);
    Incident::factory()->create(['schedule_id' => $schedule->id, 'severity' => 'CRITICA']);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/charts/incidents', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.total', 1);
});

test('returns justification statistics scoped to the career', function () {
    ['director' => $director, 'group' => $group] = makeCareerDirector();
    $schedule = makeActiveSchedule($group);
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Justification::factory()->create(['attendance_id' => $attendance->id]);

    $token = fakeGovernanceAuth($director);

    $response = $this->getJson('/api/v1/director-carrera/charts/justifications', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.by_status.PENDIENTE', 1);
});
