<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            SimpleUserSeeder::class,
            ClassroomSeeder::class,
            DeviceSeeder::class,
            SchoolYearSeeder::class,
            CareerSeeder::class,
            SubjectSeeder::class,
            GroupSeeder::class,
            UserDetailSeeder::class,
            ScheduleSeeder::class,
            AttendanceSettingSeeder::class,
            UserPermissionOverrideSeeder::class,
            DemoAccountEnrichmentSeeder::class,
        ]);
    }
}
