<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserDetailSeeder extends Seeder
{
    /**
     * UID de una tarjeta NFC física real, usado para probar el ESP32 contra la API.
     * Se repite a propósito en varios usuarios (nfc_uid ya no es unique mientras dura
     * esta fase de pruebas, ver migración de user_details) — quien tenga el id más
     * bajo entre los que la comparten es quien "gana" la resolución al tapear.
     */
    private const PHYSICAL_NFC_UID = 'hI5YmNeJxXygSOwlSTUsQuqiE9fie5gQ4ty5';

    public function run(): void
    {
        // Primero el profesor fijo, para que sea el id más bajo entre los que
        // comparten el UID físico y así sea predecible quién resuelve el tap.
        $fixedTeacher = User::where('email', 'teacher@checkmate.test')->first();

        if ($fixedTeacher !== null) {
            $this->giveNfcUid($fixedTeacher);
        }

        $teachers = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->when($fixedTeacher !== null, fn ($query) => $query->where('id', '!=', $fixedTeacher->id))
            ->get();

        $students = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))->get();

        foreach ($teachers->merge($students) as $user) {
            $this->giveNfcUid($user);
        }
    }

    private function giveNfcUid(User $user): void
    {
        UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            ['nfc_uid' => self::PHYSICAL_NFC_UID, 'qr_uuid' => (string) Str::uuid()]
        );
    }
}
