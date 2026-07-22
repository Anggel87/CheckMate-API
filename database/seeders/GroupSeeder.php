<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\Group;
use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::firstWhere('status', 'ACTIVO');
        $career = Career::firstWhere('is_active', true);

        $groups = [
            ['grade' => '1', 'section' => 'A', 'shift' => 'MATUTINO'],
            ['grade' => '2', 'section' => 'A', 'shift' => 'MATUTINO'],
            ['grade' => '3', 'section' => 'A', 'shift' => 'VESPERTINO'],
        ];

        foreach ($groups as $group) {
            Group::create([
                'school_year_id' => $schoolYear->id,
                'career_id' => $career->id,
                'is_active' => true,
                ...$group,
            ]);
        }
    }
}
