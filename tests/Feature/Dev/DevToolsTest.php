<?php

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\User;

describe('dev routes', function () {

    test('echoes the received request without authentication', function () {
        $response = $this->withHeaders(['X-Debug-Request' => 'true'])
            ->postJson('/api/v1/dev/echo?source=postman', [
                'event' => 'nfc_test',
                'nfc_uid' => '04AABBCC',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.method', 'POST')
            ->assertJsonPath('data.query.source', 'postman')
            ->assertJsonPath('data.payload.event', 'nfc_test')
            ->assertJsonPath('data.payload.nfc_uid', '04AABBCC')
            ->assertJsonMissingPath('data.payload.source');
    });

    test('activates a schedule as currently in session and returns its device', function () {
        ['schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
        // Desconfigura el horario para simular que no está vigente todavía.
        $schedule->update(['day_of_week' => 'DOMINGO', 'start_time' => '00:00:00', 'end_time' => '00:01:00']);

        $response = $this->postJson("/api/v1/dev/schedules/{$schedule->id}/activate-now", ['duration_minutes' => 45]);

        $response->assertOk()->assertJsonPath('data.device.mac_address', $device->mac_address);
        $schedule->refresh();
        expect($schedule->start_time)->not->toBe('00:00:00');
    });

    test('rejects activating a schedule whose classroom has no device', function () {
        ['schedule' => $schedule, 'device' => $device] = makeScheduleCurrentlyInSession();
        $device->delete();

        $response = $this->postJson("/api/v1/dev/schedules/{$schedule->id}/activate-now");

        $response->assertNotFound();
    });

    test('resets a schedule session and deletes its attendances', function () {
        ['schedule' => $schedule] = makeTeacherWithSchedule();
        $session = makeOpenClassSession($schedule);
        $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $schedule->group_id]);
        Attendance::factory()->create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'devices_id' => $session->device_id,
        ]);

        $response = $this->postJson("/api/v1/dev/schedules/{$schedule->id}/reset-session");

        $response->assertOk()->assertJsonPath('data.sessions_deleted', 1);
        $this->assertDatabaseMissing('class_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('attendances', ['class_session_id' => $session->id]);
    });

    test('closes a session immediately and marks unregistered students as absent', function () {
        ['group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
        $session = makeOpenClassSession($schedule);
        User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

        $response = $this->postJson("/api/v1/dev/class-sessions/{$session->id}/close-now");

        $response->assertOk()->assertJsonPath('data.status', 'CERRADA');
        expect(ClassSession::find($session->id)->status)->toBe('CERRADA');
        $this->assertDatabaseHas('attendances', ['class_session_id' => $session->id, 'status' => 'FALTA']);
    });

    test('returns the current status of a schedule and its session', function () {
        ['schedule' => $schedule] = makeTeacherWithSchedule();
        $session = makeOpenClassSession($schedule);

        $response = $this->getJson("/api/v1/dev/schedules/{$schedule->id}/status");

        $response->assertOk()->assertJsonPath('data.today_session.id', $session->id);
    });
});
