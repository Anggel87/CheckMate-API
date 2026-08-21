<?php

use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Route;

test('seeders wire the demo device and the complete NFC attendance flow', function () {
    $this->seed(DatabaseSeeder::class);

    $device = Device::where('mac_address', 'DC:A6:32:69:68:11')->firstOrFail();
    $teacher = User::where('email', 'profesor@checkmate.com')->firstOrFail();
    $student = User::where('email', 'alumno@checkmate.com')->firstOrFail();
    $schedule = Schedule::query()
        ->where('teacher_id', $teacher->id)
        ->where('classroom_id', $device->classroom_id)
        ->where('group_id', $student->group_id)
        ->firstOrFail();

    expect($device->is_active)->toBeTrue()
        ->and($teacher->details->nfc_uid)->toBe('hI5YmNeJxXygSOwlSTUsQuqiE9fie5gQ4ty5')
        ->and($student->details->nfc_uid)->toBe('B30428070000000000000000000000000000');

    Route::prefix('api/v1')->middleware('api')->group(base_path('routes/api/dev.php'));

    $this->postJson("/api/v1/dev/schedules/{$schedule->id}/activate-now")
        ->assertOk()
        ->assertJsonPath('data.device.mac_address', $device->mac_address);

    $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => $teacher->details->nfc_uid,
    ])->assertCreated()->assertJsonPath('data.event', 'session_opened');

    $session = ClassSession::where('schedule_id', $schedule->id)->whereDate('date', now())->firstOrFail();

    $this->postJson('/api/v1/device/nfc', [
        'mac_address' => $device->mac_address,
        'nfc_uid' => $student->details->nfc_uid,
        'scanned_at' => $session->opened_at->copy()->addMinutes(5)->format('Y-m-d\\TH:i:s'),
    ])->assertCreated()
        ->assertJsonPath('data.event', 'attendance_registered')
        ->assertJsonPath('data.student_id', $student->id)
        ->assertJsonPath('data.status', 'PRESENTE');
});
