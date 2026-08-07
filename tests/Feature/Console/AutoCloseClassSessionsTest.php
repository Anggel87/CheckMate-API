<?php

use App\Models\Attendance;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Support\Carbon;

test('closes a class session whose schedule already ended and marks missing students as FALTA', function () {
    ['group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['end_time' => '23:59:00']);

    $session = makeOpenClassSession($schedule, Carbon::yesterday());

    $registered = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    $missing = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $tutor = Tutor::factory()->create();
    $missing->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    Attendance::factory()->create([
        'class_session_id' => $session->id,
        'student_id' => $registered->id,
        'schedule_id' => $schedule->id,
        'devices_id' => $session->device_id,
        'status' => 'PRESENTE',
    ]);

    $this->artisan('class-sessions:auto-close')->assertExitCode(0);

    $session->refresh();
    expect($session->status)->toBe('CERRADA');
    $this->assertDatabaseHas('attendances', [
        'class_session_id' => $session->id,
        'student_id' => $missing->id,
        'status' => 'FALTA',
        'method' => 'SISTEMA',
    ]);
    $this->assertDatabaseHas('notifications', [
        'student_id' => $missing->id,
        'tutor_id' => $tutor->id,
        'type' => 'INASISTENCIA',
    ]);
});

test('leaves a class session open while its schedule has not ended yet', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule();
    $schedule->update(['end_time' => now()->addHour()->format('H:i:s')]);

    $session = makeOpenClassSession($schedule);

    $this->artisan('class-sessions:auto-close')->assertExitCode(0);

    $session->refresh();
    expect($session->status)->toBe('ABIERTA');
});
