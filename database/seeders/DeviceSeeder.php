<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Classroom::all() as $classroom) {
            Device::firstOrCreate(
                ['classroom_id' => $classroom->id],
                [
                    'mac_address' => strtoupper(fake()->unique()->regexify('([0-9A-F]{2}:){5}[0-9A-F]{2}')),
                    'ip' => fake()->localIpv4(),
                    'is_active' => true,
                ]
            );
        }
    }
}
