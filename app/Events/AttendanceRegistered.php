<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Foundation\Events\Dispatchable;

class AttendanceRegistered
{
    use Dispatchable;

    public function __construct(public Attendance $attendance, public ?int $performedByUserId) {}
}
