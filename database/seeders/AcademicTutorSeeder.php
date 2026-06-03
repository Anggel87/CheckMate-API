<?php

namespace Database\Seeders;

use App\Models\AcademicTutor;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class AcademicTutorSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::doesntHave('academicTutor')->take(4)->get();

        foreach ($teachers as $teacher) {
            AcademicTutor::create([
                'teacher_id' => $teacher->id,
                'is_active' => true,
            ]);
        }
    }
}
