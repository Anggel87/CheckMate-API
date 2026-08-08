<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\AcademicTutor;
use App\Models\Career;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use App\Support\DayOfWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Fakes gobernanza's GET /auth/me so a request with "Authorization: Bearer {token}"
 * resolves to the given local user via ResolveGovernanceUser middleware.
 *
 * Calling this multiple times in the same test registers multiple token => user
 * mappings (kept in the container, which Laravel resets per test) instead of each
 * call silently overriding the previous one — needed for tests where two different
 * roles act in the same test (e.g. a tutor reviews something, then the student reads
 * it back).
 */
function fakeGovernanceAuth(User $user, string $token = 'test-governance-token'): string
{
    $registry = app()->bound('governance.test.registry') ? app('governance.test.registry') : [];

    $registry[$token] = [
        'id' => $user->governance_user_id,
        'name' => $user->fullName(),
        'email' => $user->email,
        'role' => $user->role->name,
    ];

    app()->instance('governance.test.registry', $registry);

    Http::fake([
        '*/auth/me' => function ($request) {
            $header = $request->header('Authorization')[0] ?? '';
            $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $header;
            $entry = (app('governance.test.registry'))[$token] ?? null;

            if ($entry === null) {
                return Http::response(['message' => 'No autenticado.'], 401);
            }

            return Http::response([
                'message' => 'Usuario autenticado.',
                'data' => ['user' => $entry],
            ], 200);
        },
    ]);

    return $token;
}

/**
 * Creates a Group tied to an ACTIVO school year, ready to enroll a student in.
 */
function makeActiveGroup(): Group
{
    $schoolYear = SchoolYear::factory()->active()->create();
    $career = Career::factory()->create();

    return Group::factory()->create([
        'school_year_id' => $schoolYear->id,
        'career_id' => $career->id,
    ]);
}

/**
 * Creates an active Schedule (subject + teacher + classroom) for the given group,
 * within its ACTIVO school year.
 */
function makeActiveSchedule(Group $group, ?Subject $subject = null): Schedule
{
    return Schedule::factory()->create([
        'school_year_id' => $group->school_year_id,
        'group_id' => $group->id,
        'subject_id' => ($subject ?? Subject::factory()->create())->id,
        'is_active' => true,
    ]);
}

/**
 * Creates a teacher with an active Schedule for the given (or a new) active Group.
 *
 * @return array{teacher: User, group: Group, schedule: Schedule}
 */
function makeTeacherWithSchedule(?Group $group = null, ?Subject $subject = null, int $governanceUserId = 1): array
{
    $group ??= makeActiveGroup();
    $classroom = Classroom::factory()->create();

    $teacher = User::factory()->teacher()->create(['governance_user_id' => $governanceUserId]);

    $schedule = Schedule::factory()->create([
        'school_year_id' => $group->school_year_id,
        'group_id' => $group->id,
        'teacher_id' => $teacher->id,
        'subject_id' => ($subject ?? Subject::factory()->create())->id,
        'classroom_id' => $classroom->id,
        'is_active' => true,
    ]);

    return ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule];
}

/**
 * Creates an academic tutor actively assigned to the given Group.
 */
function makeTutorForGroup(Group $group, int $governanceUserId = 1): User
{
    $tutor = User::factory()->academicTutor()->create(['governance_user_id' => $governanceUserId]);
    $academicTutor = AcademicTutor::factory()->create(['user_id' => $tutor->id]);
    $academicTutor->groups()->attach($group->id, ['is_active' => true, 'assigned_at' => now()]);

    return $tutor;
}

/**
 * Opens a ClassSession for the given Schedule on the given date (defaults to today),
 * with an active Device attached to the schedule's classroom.
 */
function makeOpenClassSession(Schedule $schedule, ?Carbon $date = null): ClassSession
{
    $date ??= now();

    $device = Device::factory()->create(['classroom_id' => $schedule->classroom_id]);

    return ClassSession::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => $date->format('Y-m-d'),
        'teacher_id' => $schedule->teacher_id,
        'device_id' => $device->id,
        'opened_at' => $date,
        'closed_at' => null,
        'status' => 'ABIERTA',
        'is_active' => true,
    ]);
}

/**
 * Creates a teacher with a Schedule + Device wired to the same classroom, with the
 * schedule's day_of_week/start_time/end_time set so it's "in session" right now (or at
 * the given moment) — ready to hit the unauthenticated device NFC endpoint
 * (POST /api/v1/device/nfc), which resolves the current class purely from
 * classroom + day + time window.
 *
 * @return array{teacher: User, group: Group, schedule: Schedule, device: Device}
 */
function makeScheduleCurrentlyInSession(?Carbon $now = null): array
{
    $now ??= Carbon::now();

    ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule] = makeTeacherWithSchedule();

    $schedule->update([
        'day_of_week' => DayOfWeek::fromCarbon($now),
        'start_time' => $now->copy()->subMinutes(5)->format('H:i:s'),
        'end_time' => $now->copy()->addHour()->format('H:i:s'),
    ]);

    $device = Device::factory()->create(['classroom_id' => $schedule->classroom_id]);

    return ['teacher' => $teacher, 'group' => $group, 'schedule' => $schedule, 'device' => $device];
}

/**
 * Creates a local user with the administrador role, ready for fakeGovernanceAuth().
 */
function makeAdmin(int $governanceUserId = 1): User
{
    return User::factory()->administrator()->create(['governance_user_id' => $governanceUserId]);
}

/**
 * Fakes gobernanza's POST /internal/users so GovernanceClient::createUser() resolves
 * without a real call. Only adds the "internal/users" pattern — does not touch the
 * "auth/me" one, so it's safe to call alongside fakeGovernanceAuth() in the same test.
 */
function fakeGovernanceCreateUser(int $governanceUserId = 500, string $temporaryPassword = 'Temp1234!'): void
{
    Http::fake([
        '*/internal/users' => Http::response([
            'data' => [
                'user' => ['id' => $governanceUserId],
                'temporary_password' => $temporaryPassword,
            ],
        ], 201),
    ]);
}

/**
 * Creates a career director (role director_carrera) with a Career whose director_id
 * points at them, plus a Group inside that career (active school year) ready to enroll
 * students/teachers/schedules in for scope tests.
 *
 * @return array{director: User, career: Career, group: Group}
 */
function makeCareerDirector(int $governanceUserId = 1): array
{
    $director = User::factory()->careerDirector()->create(['governance_user_id' => $governanceUserId]);
    $career = Career::factory()->create(['director_id' => $director->id]);
    $schoolYear = SchoolYear::factory()->active()->create();
    $group = Group::factory()->create(['career_id' => $career->id, 'school_year_id' => $schoolYear->id]);

    return ['director' => $director, 'career' => $career, 'group' => $group];
}
