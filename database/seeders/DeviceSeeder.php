<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    private const DEMO_MAC_ADDRESS = 'DC:A6:32:69:68:11';

    public function run(): void
    {
        $classrooms = Classroom::query()->orderBy('id')->get();
        $demoClassroom = $classrooms->firstWhere('name', 'Aula 101') ?? $classrooms->first();

        if ($demoClassroom === null) {
            return;
        }

        $demoDevice = Device::where('mac_address', self::DEMO_MAC_ADDRESS)->first();

        if ($demoDevice === null) {
            $demoDevice = Device::where('classroom_id', $demoClassroom->id)->first();

            if ($demoDevice === null) {
                $demoDevice = Device::create([
                    'mac_address' => self::DEMO_MAC_ADDRESS,
                    'ip' => fake()->localIpv4(),
                    'is_active' => true,
                    'classroom_id' => $demoClassroom->id,
                ]);
            } else {
                $demoDevice->update([
                    'mac_address' => self::DEMO_MAC_ADDRESS,
                    'is_active' => true,
                ]);
            }
        } elseif ($demoDevice->classroom_id !== $demoClassroom->id) {
            $demoDevice->update(['classroom_id' => $demoClassroom->id]);
        }

        foreach ($classrooms as $classroom) {
            if ($classroom->is($demoClassroom)) {
                $demoDevice->update(['is_active' => true]);

                continue;
            }

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
