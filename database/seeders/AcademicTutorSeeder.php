<?php

namespace Database\Seeders;

use App\Models\AcademicTutor;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicTutorSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->doesntHave('academicTutor')
            ->take(4)
            ->get();

        foreach ($teachers as $teacher) {
            AcademicTutor::create([
                'user_id' => $teacher->id,
                'is_active' => true,
            ]);
        }
    }
}
