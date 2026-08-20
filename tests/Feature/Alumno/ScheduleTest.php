<?php

use App\Models\User;

test('groups the student weekly schedule by day', function () {
    $group = makeActiveGroup();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $schedule->update(['day_of_week' => 'LUNES']);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/schedule', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.LUNES.0.schedule_id', $schedule->id)
        ->assertJsonPath('data.LUNES.0.teacher.id', $schedule->teacher_id);
});

test('orders classes within a day by start time', function () {
    $group = makeActiveGroup();
    ['schedule' => $later] = makeTeacherWithSchedule($group, null, 2);
    $later->update(['day_of_week' => 'MARTES', 'start_time' => '11:00:00', 'end_time' => '12:00:00']);

    ['schedule' => $earlier] = makeTeacherWithSchedule($group, null, 3);
    $earlier->update(['day_of_week' => 'MARTES', 'start_time' => '08:00:00', 'end_time' => '09:00:00']);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/schedule', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.MARTES.0.schedule_id', $earlier->id)
        ->assertJsonPath('data.MARTES.1.schedule_id', $later->id);
});

test('filters the schedule by day', function () {
    $group = makeActiveGroup();
    ['schedule' => $schedule] = makeTeacherWithSchedule($group, null, 2);
    $schedule->update(['day_of_week' => 'VIERNES']);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/schedule?day=VIERNES', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data.VIERNES');
});

test('validates the day query parameter', function () {
    $student = User::factory()->student()->create(['governance_user_id' => 1]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/schedule?day=NOTADAY', ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable()->assertJsonPath('error_code', 'VAL01');
});

test('does not include another group schedule', function () {
    $group = makeActiveGroup();
    $otherGroup = makeActiveGroup();
    makeTeacherWithSchedule($otherGroup, null, 2);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/schedule', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(0, 'data');
});
