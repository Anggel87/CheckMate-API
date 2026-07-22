<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $groups = Group::all()->where('is_active', true);
        $tutors = Tutor::all();
        $relationships = ['Madre', 'Padre', 'Tutor', 'Abuelo', 'Abuela', 'Tio', 'Tia'];

        foreach ($groups as $group) {
            $students = User::factory()
                ->student()
                ->count(7)
                ->create(['group_id' => $group->id]);

            foreach ($students as $student) {
                $primaryTutor = $tutors->random();

                $student->tutors()->attach($primaryTutor->id, [
                    'relationship' => fake()->randomElement($relationships),
                    'is_primary' => true,
                    'receives_notifications' => true,
                ]);

                if (fake()->boolean(40)) {
                    $secondTutor = $tutors->where('id', '!=', $primaryTutor->id)->random();

                    $student->tutors()->attach($secondTutor->id, [
                        'relationship' => fake()->randomElement($relationships),
                        'is_primary' => false,
                        'receives_notifications' => fake()->boolean(70),
                    ]);
                }
            }
        }
    }
}
