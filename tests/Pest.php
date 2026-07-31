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

use App\Models\Career;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
 */
function fakeGovernanceAuth(User $user, string $token = 'test-governance-token'): string
{
    Http::fake([
        '*/auth/me' => Http::response([
            'message' => 'Usuario autenticado.',
            'data' => [
                'user' => [
                    'id' => $user->governance_user_id,
                    'name' => $user->fullName(),
                    'email' => $user->email,
                    'role' => $user->role->name,
                ],
            ],
        ], 200),
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
