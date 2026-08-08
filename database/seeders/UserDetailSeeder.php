<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserDetailSeeder extends Seeder
{
    /**
     * UIDs de tarjetas NFC físicas reales, usados para probar el ESP32 contra la API.
     * Cada uno se repite a propósito dentro de su grupo (nfc_uid ya no es unique
     * mientras dura esta fase de pruebas, ver migración de user_details) — quien tenga
     * el id más bajo entre los que comparten un UID es quien "gana" la resolución al
     * tapear esa tarjeta.
     */
    private const TEACHER_NFC_UID = 'hI5YmNeJxXygSOwlSTUsQuqiE9fie5gQ4ty5';

    private const STUDENT_NFC_UID = 'B30428070000000000000000000000000000';

    public function run(): void
    {
        // Primero el profesor fijo, para que sea el id más bajo entre los que
        // comparten el UID físico de profesor y así sea predecible quién resuelve el tap.
        $fixedTeacher = User::where('email', 'teacher@checkmate.test')->first();

        if ($fixedTeacher !== null) {
            $this->giveNfcUid($fixedTeacher, self::TEACHER_NFC_UID);
        }

        $teachers = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->when($fixedTeacher !== null, fn ($query) => $query->where('id', '!=', $fixedTeacher->id))
            ->get();

        foreach ($teachers as $teacher) {
            $this->giveNfcUid($teacher, self::TEACHER_NFC_UID);
        }

        $students = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))->get();

        foreach ($students as $student) {
            $this->giveNfcUid($student, self::STUDENT_NFC_UID);
        }
    }

    private function giveNfcUid(User $user, string $nfcUid): void
    {
        UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            ['nfc_uid' => $nfcUid, 'qr_uuid' => (string) Str::uuid()]
        );
    }
}
