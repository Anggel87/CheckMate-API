<?php

use App\Support\DayOfWeek;
use Illuminate\Support\Carbon;

test('lists only today schedules with session_open flag', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();

    $today = Carbon::now(config('app.timezone'));
    $schedule->update(['day_of_week' => DayOfWeek::fromCarbon($today)]);

    $session = makeOpenClassSession($schedule, $today);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/schedule/today', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.schedule_id', $schedule->id)
        ->assertJsonPath('data.0.session_open', true)
        ->assertJsonPath('data.0.session_id', $session->id);
});

test('groups the weekly schedule by day', function () {
    ['teacher' => $teacher, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['day_of_week' => 'LUNES']);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/schedule', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.LUNES.0.schedule_id', $schedule->id);
});

test('validates the day query parameter', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/schedule?day=NOTADAY', ['Authorization' => "Bearer {$token}"]);

    $response->assertUnprocessable()->assertJsonPath('error_code', 'VAL01');
});
