<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdministratorSeeder::class,
            ClassroomSeeder::class,
            SchoolYearSeeder::class,
            DirectorSeeder::class,
            CareerSeeder::class,
            SubjectSeeder::class,
            TeacherSeeder::class,
            AcademicTutorSeeder::class,
            TutorSeeder::class,
            GroupSeeder::class,
            StudentSeeder::class,
            ScheduleSeeder::class,
        ]);
    }
}
