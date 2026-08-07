<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Database\Seeder;

class UserPermissionOverrideSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@checkmate.test')->first();
        $incidentsView = Permission::where('key_name', 'incidents.view')->first();

        if ($teacher !== null && $incidentsView !== null) {
            UserPermissionOverride::updateOrCreate(
                ['users_id' => $teacher->id, 'permissions_id' => $incidentsView->id],
                ['type' => 'DENEGAR']
            );
        }

        $student = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))->orderBy('id')->first();
        $reportsView = Permission::where('key_name', 'reports.view')->first();

        if ($student !== null && $reportsView !== null) {
            UserPermissionOverride::updateOrCreate(
                ['users_id' => $student->id, 'permissions_id' => $reportsView->id],
                ['type' => 'PERMITIR']
            );
        }
    }
}
