<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class DayOfWeek
{
    private const NAMES = [
        Carbon::MONDAY => 'LUNES',
        Carbon::TUESDAY => 'MARTES',
        Carbon::WEDNESDAY => 'MIERCOLES',
        Carbon::THURSDAY => 'JUEVES',
        Carbon::FRIDAY => 'VIERNES',
        Carbon::SATURDAY => 'SABADO',
        Carbon::SUNDAY => 'DOMINGO',
    ];

    public static function fromCarbon(Carbon $date): string
    {
        return self::NAMES[$date->dayOfWeek];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(self::NAMES);
    }
}
