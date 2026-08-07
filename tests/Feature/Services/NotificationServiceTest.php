<?php

use App\Models\Attendance;
use App\Models\Tutor;
use App\Models\User;
use App\Services\NotificationService;

test('notifies a tutor who wants absence alerts and receives notifications for this student', function () {
    $student = User::factory()->student()->create();

    $tutorA = Tutor::factory()->create();
    $tutorB = Tutor::factory()->create();

    $student->tutors()->attach($tutorA->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);
    $student->tutors()->attach($tutorB->id, ['relationship' => 'Padre', 'is_primary' => false, 'receives_notifications' => false]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id]);

    app(NotificationService::class)->notifyAbsence($attendance);

    $this->assertDatabaseHas('notifications', [
        'tutor_id' => $tutorA->id,
        'student_id' => $student->id,
        'type' => 'INASISTENCIA',
    ]);
    $this->assertDatabaseMissing('notifications', ['tutor_id' => $tutorB->id]);
});

test('does not notify a tutor who disabled late alerts in their preferences', function () {
    $student = User::factory()->student()->create();
    $tutor = Tutor::factory()->create();
    $tutor->notificationPreference->update(['lates' => false]);
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    $attendance = Attendance::factory()->late()->create(['student_id' => $student->id]);

    app(NotificationService::class)->notifyLate($attendance);

    $this->assertDatabaseMissing('notifications', ['tutor_id' => $tutor->id]);
});

test('creating a tutor automatically creates its notification preferences with everything enabled', function () {
    $tutor = Tutor::factory()->create();

    $this->assertDatabaseHas('notification_preferences', [
        'tutor_id' => $tutor->id,
        'absences' => 1,
        'lates' => 1,
        'incidents' => 1,
        'justifications' => 1,
        'claims' => 1,
        'announcements' => 1,
    ]);
});
