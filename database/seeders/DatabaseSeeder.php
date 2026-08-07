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
            AdministratorSeeder::class,
            ClassroomSeeder::class,
            DeviceSeeder::class,
            SchoolYearSeeder::class,
            DirectorSeeder::class,
            CareerSeeder::class,
            SubjectSeeder::class,
            TeacherSeeder::class,
            AcademicTutorSeeder::class,
            TutorSeeder::class,
            GroupSeeder::class,
            StudentSeeder::class,
            UserDetailSeeder::class,
            ScheduleSeeder::class,
            AttendanceSettingSeeder::class,
            AttendanceSeeder::class,
            IncidentSeeder::class,
            ClaimSeeder::class,
            JustificationSeeder::class,
            AppNotificationSeeder::class,
            UserPermissionOverrideSeeder::class,
        ]);
    }
}
