<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\Subject;
use App\Models\User;

test('lists subjects scheduled for the student group', function () {
    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson('/api/v1/alumno/subjects', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $subject->id)
        ->assertJsonPath('data.0.teacher.id', $schedule->teacher_id);
});

test('rejects viewing a subject outside the student group', function () {
    $group = makeActiveGroup();
    $otherSubject = Subject::factory()->create();

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);
    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/subjects/{$otherSubject->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('shows the attendance summary for a subject', function () {
    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    Attendance::factory()->present()->count(2)->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Attendance::factory()->late()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/subjects/{$subject->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.attendance_summary.on_time', 2)
        ->assertJsonPath('data.attendance_summary.late', 1)
        ->assertJsonPath('data.attendance_summary.absent', 1);
});

test('flags absent attendance without a justification as justifiable', function () {
    $group = makeActiveGroup();
    $subject = Subject::factory()->create();
    $schedule = makeActiveSchedule($group, $subject);

    $student = User::factory()->student()->create(['governance_user_id' => 1, 'group_id' => $group->id]);

    $unjustified = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $justifiedAttendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Justification::factory()->create(['attendance_id' => $justifiedAttendance->id]);

    $token = fakeGovernanceAuth($student);

    $response = $this->getJson("/api/v1/alumno/subjects/{$subject->id}/attendance", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk();

    $byId = collect($response->json('data'))->keyBy('attendance_id');

    expect($byId[$unjustified->id]['justifiable'])->toBeTrue();
    expect($byId[$justifiedAttendance->id]['justifiable'])->toBeFalse();
});
