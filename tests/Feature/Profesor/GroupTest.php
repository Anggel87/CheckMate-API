<?php

use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;

test('lists groups where the teacher has an active schedule', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();

    User::factory()->student()->count(2)->create(['governance_user_id' => null, 'group_id' => $group->id]);
    User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id, 'active' => false]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/groups', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $group->id)
        ->assertJsonPath('data.0.student_count', 2);
});

test('lists active students of a group the teacher teaches', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/groups/{$group->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $student->id);
});

test('computes attendance summary for each student without per-student requests', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    Attendance::factory()->count(3)->present()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Attendance::factory()->late()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $absentAttendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Justification::factory()->create(['attendance_id' => $absentAttendance->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/groups/{$group->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.0.attendance_rate', 60)
        ->assertJsonPath('data.0.absence_count', 1)
        ->assertJsonPath('data.0.late_count', 1)
        ->assertJsonPath('data.0.justification_count', 1)
        ->assertJsonPath('data.0.today_present_count', 4)
        ->assertJsonPath('data.0.today_absent_count', 1)
        ->assertJsonPath('data.0.weekly_absence_count', 1);
});

test('excludes attendance recorded under another teacher from the summary', function () {
    ['teacher' => $teacher, 'group' => $group] = makeTeacherWithSchedule();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule($group, null, 2);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $otherSchedule->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/groups/{$group->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonPath('data.0.attendance_rate', 0)
        ->assertJsonPath('data.0.absence_count', 0);
});

test('academic tutor lists groups assigned via the academic tutor pivot even without teaching there', function () {
    $group = makeActiveGroup();
    $tutor = makeTutorForGroup($group, 2);

    User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->getJson('/api/v1/profesor/groups', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $group->id);
});

test('academic tutor lists and sees the attendance summary for a group they do not personally teach', function () {
    ['group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $tutor = makeTutorForGroup($group, 2);

    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);
    $absentAttendance = Attendance::factory()->absent()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    Justification::factory()->create(['attendance_id' => $absentAttendance->id]);

    $token = fakeGovernanceAuth($tutor);

    $response = $this->getJson("/api/v1/profesor/groups/{$group->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.absence_count', 1)
        ->assertJsonPath('data.0.justification_count', 1);
});

test('rejects listing students of a group the teacher does not teach', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $otherGroup = makeActiveGroup();

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/groups/{$otherGroup->id}/students", ['Authorization' => "Bearer {$token}"]);

    $response->assertForbidden()->assertJsonPath('error_code', 'PERM01');
});

test('returns 404 for a nonexistent group', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/groups/999999/students', ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'GRP02');
});
