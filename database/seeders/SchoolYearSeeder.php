<?php

namespace Database\Seeders;

use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder
{
    public function run(): void
    {
        SchoolYear::create([
            'name' => '2024-2025',
            'start_date' => '2024-08-01',
            'end_date' => '2025-06-30',
            'status' => 'FINISHED',
        ]);

        SchoolYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-08-01',
            'end_date' => '2026-06-30',
            'status' => 'ACTIVE',
        ]);
    }
}
