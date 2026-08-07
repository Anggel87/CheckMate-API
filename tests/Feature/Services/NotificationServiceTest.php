<?php

use App\Models\Attendance;
use App\Models\Tutor;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;

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

test('sends a WhatsApp message to the tutor when Twilio is configured', function () {
    config([
        'services.twilio.account_sid' => 'AC_test_sid',
        'services.twilio.auth_token' => 'test_token',
        'services.twilio.whatsapp_from' => 'whatsapp:+14155238886',
    ]);
    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201)]);

    $student = User::factory()->student()->create();
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id]);

    app(NotificationService::class)->notifyAbsence($attendance);

    Http::assertSent(fn ($request) => $request['To'] === 'whatsapp:+52'.$tutor->phone);
});

test('still creates the notification even if the WhatsApp send fails', function () {
    config([
        'services.twilio.account_sid' => 'AC_test_sid',
        'services.twilio.auth_token' => 'test_token',
        'services.twilio.whatsapp_from' => 'whatsapp:+14155238886',
    ]);
    Http::fake(['api.twilio.com/*' => Http::response(['message' => 'Error'], 500)]);

    $student = User::factory()->student()->create();
    $tutor = Tutor::factory()->create();
    $student->tutors()->attach($tutor->id, ['relationship' => 'Madre', 'is_primary' => true, 'receives_notifications' => true]);

    $attendance = Attendance::factory()->absent()->create(['student_id' => $student->id]);

    app(NotificationService::class)->notifyAbsence($attendance);

    $this->assertDatabaseHas('notifications', ['tutor_id' => $tutor->id, 'student_id' => $student->id, 'type' => 'INASISTENCIA']);
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
