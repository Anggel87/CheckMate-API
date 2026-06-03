<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\Director;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    private static array $careers = [
        ['name' => 'Tecnologías de la Información y Comunicación', 'short' => 'TIC', 'code' => 'TIC'],
        ['name' => 'Administración de Empresas', 'short' => 'ADM', 'code' => 'ADM'],
        ['name' => 'Contabilidad y Finanzas', 'short' => 'CONT', 'code' => 'CONT'],
        ['name' => 'Turismo y Hospitalidad', 'short' => 'TUR', 'code' => 'TUR'],
        ['name' => 'Mecatrónica Industrial', 'short' => 'MEC', 'code' => 'MEC'],
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
            'directors_id' => Director::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
