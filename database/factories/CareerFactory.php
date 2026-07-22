<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    private static array $careers = [
        ['name' => 'Tecnologias de la Informacion y Comunicacion', 'short' => 'TIC', 'code' => 'TIC'],
        ['name' => 'Administracion de Empresas', 'short' => 'ADM', 'code' => 'ADM'],
        ['name' => 'Contabilidad y Finanzas', 'short' => 'CONT', 'code' => 'CONT'],
        ['name' => 'Turismo y Hospitalidad', 'short' => 'TUR', 'code' => 'TUR'],
        ['name' => 'Mecatronica Industrial', 'short' => 'MEC', 'code' => 'MEC'],
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $career = fake()->randomElement(self::$careers);

        return [
            'name' => $career['name'],
            'short_name' => $career['short'],
            'code' => $career['code'].'-'.fake()->numberBetween(1, 9),
            'is_active' => true,
            'director_id' => User::factory()->careerDirector(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
