<?php

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;

function scheduleAdminPayload(array $overrides = []): array
{
    $group = $overrides['group'] ?? makeActiveGroup();
    $classroom = Classroom::factory()->create();
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 2]);
    $subject = Subject::factory()->create();

    return [
        'school_year_id' => $group->school_year_id,
        'group_id' => $group->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'day_of_week' => 'LUNES',
        'start_time' => '08:00',
        'end_time' => '09:00',
        ...$overrides,
    ];
}

test('lists schedules', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/schedules', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.id'))->toBe($schedule->id);
});

test('creates a schedule', function () {
    $group = makeActiveGroup();
    $classroom = Classroom::factory()->create();
    $teacher = User::factory()->teacher()->create(['governance_user_id' => 2]);
    $subject = Subject::factory()->create();
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/schedules', [
        'school_year_id' => $group->school_year_id,
        'group_id' => $group->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'classroom_id' => $classroom->id,
        'day_of_week' => 'LUNES',
        'start_time' => '08:00',
        'end_time' => '09:00',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated()
        ->assertJsonPath('data.day_of_week', 'LUNES')
        ->assertJsonPath('data.start_time', '08:00')
        ->assertJsonPath('data.teacher.id', $teacher->id);

    $this->assertDatabaseHas('schedules', [
        'group_id' => $group->id,
        'teacher_id' => $teacher->id,
        'day_of_week' => 'LUNES',
        'is_active' => true,
    ]);
});

test('rejects a schedule referencing a missing teacher', function () {
    $group = makeActiveGroup();
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/schedules', scheduleAdminPayload([
        'group' => $group,
        'teacher_id' => 999999,
    ]), ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR06');
});

test('rejects a schedule where the teacher_id belongs to a student, not a teacher', function () {
    $group = makeActiveGroup();
    $student = User::factory()->student()->create(['group_id' => $group->id]);
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/schedules', scheduleAdminPayload([
        'group' => $group,
        'teacher_id' => $student->id,
    ]), ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'USR06');
});

test('rejects overlapping schedules for the same teacher', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $existing] = makeTeacherWithSchedule();
    $existing->update(['day_of_week' => 'LUNES', 'start_time' => '08:00:00', 'end_time' => '09:00:00']);
    $token = fakeGovernanceAuth(makeAdmin(999));

    $otherGroup = makeActiveGroup();

    $response = $this->postJson('/api/v1/administrador/schedules', scheduleAdminPayload([
        'group' => $otherGroup,
        'school_year_id' => $existing->school_year_id,
        'teacher_id' => $teacher->id,
        'day_of_week' => 'LUNES',
        'start_time' => '08:30',
        'end_time' => '09:30',
    ]), ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SCH02');
});

test('rejects overlapping schedules for the same classroom', function () {
    ['group' => $group, 'schedule' => $existing] = makeTeacherWithSchedule();
    $existing->update(['day_of_week' => 'MARTES', 'start_time' => '10:00:00', 'end_time' => '11:00:00']);
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/schedules', scheduleAdminPayload([
        'group' => makeActiveGroup(),
        'school_year_id' => $existing->school_year_id,
        'classroom_id' => $existing->classroom_id,
        'day_of_week' => 'MARTES',
        'start_time' => '10:30',
        'end_time' => '11:30',
    ]), ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SCH02');
});

test('rejects overlapping schedules for the same group', function () {
    ['group' => $group, 'schedule' => $existing] = makeTeacherWithSchedule();
    $existing->update(['day_of_week' => 'MIERCOLES', 'start_time' => '07:00:00', 'end_time' => '08:00:00']);
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/schedules', scheduleAdminPayload([
        'group' => $group,
        'day_of_week' => 'MIERCOLES',
        'start_time' => '07:30',
        'end_time' => '08:30',
    ]), ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SCH02');
});

test('allows a non overlapping schedule for the same teacher on a different time', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $existing] = makeTeacherWithSchedule();
    $existing->update(['day_of_week' => 'LUNES', 'start_time' => '08:00:00', 'end_time' => '09:00:00']);
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->postJson('/api/v1/administrador/schedules', scheduleAdminPayload([
        'group' => makeActiveGroup(),
        'teacher_id' => $teacher->id,
        'day_of_week' => 'LUNES',
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]), ['Authorization' => "Bearer {$token}"]);

    $response->assertCreated();
});

test('updates a schedule', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->putJson("/api/v1/administrador/schedules/{$schedule->id}", [
        'start_time' => '11:00',
        'end_time' => '12:00',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonPath('data.start_time', '11:00');
});

test('rejects updating a schedule into a conflict with another one', function () {
    $group = makeActiveGroup();
    ['teacher' => $teacher, 'schedule' => $first] = makeTeacherWithSchedule($group);
    $first->update(['day_of_week' => 'JUEVES', 'start_time' => '08:00:00', 'end_time' => '09:00:00']);

    $second = Schedule::factory()->create([
        'school_year_id' => $group->school_year_id,
        'group_id' => $group->id,
        'teacher_id' => User::factory()->teacher()->create(['governance_user_id' => 3])->id,
        'day_of_week' => 'JUEVES',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'is_active' => true,
    ]);

    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->putJson("/api/v1/administrador/schedules/{$second->id}", [
        'teacher_id' => $teacher->id,
        'start_time' => '08:30',
        'end_time' => '09:30',
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertConflict()->assertJsonPath('error_code', 'SCH02');
});

test('deactivates a schedule when confirmed', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->deleteJson("/api/v1/administrador/schedules/{$schedule->id}", ['confirm' => true], ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();
    $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'is_active' => false]);
});

test('requires confirmation to deactivate a schedule', function () {
    ['schedule' => $schedule] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->deleteJson("/api/v1/administrador/schedules/{$schedule->id}", [], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(422)->assertJsonPath('error_code', 'VAL03');
});

test('returns 404 for a nonexistent schedule', function () {
    $token = fakeGovernanceAuth(makeAdmin(999));

    $response = $this->getJson('/api/v1/administrador/schedules/999999', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'SCH01');
});
