<?php

use App\Models\Attendance;
use App\Models\Claim;
use App\Models\User;

test('lists only claims about the teacher own subjects', function () {
    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();
    $student = User::factory()->student()->create(['governance_user_id' => null, 'group_id' => $group->id]);

    $mineAttendance = Attendance::factory()->create(['student_id' => $student->id, 'schedule_id' => $schedule->id]);
    $mine = Claim::factory()->create(['attendance_id' => $mineAttendance->id, 'tutor_id' => $student->id]);

    ['schedule' => $otherSchedule] = makeTeacherWithSchedule(governanceUserId: 2);
    $otherAttendance = Attendance::factory()->create(['schedule_id' => $otherSchedule->id]);
    Claim::factory()->create(['attendance_id' => $otherAttendance->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson('/api/v1/profesor/claims', ['Authorization' => "Bearer {$token}"]);

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('returns 404 for a claim outside the teacher subjects', function () {
    ['teacher' => $teacher] = makeTeacherWithSchedule();
    ['schedule' => $otherSchedule] = makeTeacherWithSchedule(governanceUserId: 2);
    $attendance = Attendance::factory()->create(['schedule_id' => $otherSchedule->id]);
    $claim = Claim::factory()->create(['attendance_id' => $attendance->id]);

    $token = fakeGovernanceAuth($teacher);

    $response = $this->getJson("/api/v1/profesor/claims/{$claim->id}", ['Authorization' => "Bearer {$token}"]);

    $response->assertNotFound()->assertJsonPath('error_code', 'CLM01');
});
