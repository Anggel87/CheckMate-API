<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'STUDENT',
        ];
    }

    public function admin(): static
    {
        return $this->state(['name' => 'ADMIN']);
    }

    public function director(): static
    {
        return $this->state(['name' => 'DIRECTOR']);
    }

    public function teacher(): static
    {
        return $this->state(['name' => 'TEACHER']);
    }

    public function student(): static
    {
        return $this->state(['name' => 'STUDENT']);
    }
}
