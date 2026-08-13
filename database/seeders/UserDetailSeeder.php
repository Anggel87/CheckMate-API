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
     * El profesor y el alumno demo tienen UIDs conocidos; el resto recibe un UID
     * diferente para que una tarjeta nunca resuelva accidentalmente a otro usuario.
     */
    private const TEACHER_NFC_UID = 'hI5YmNeJxXygSOwlSTUsQuqiE9fie5gQ4ty5';

    private const STUDENT_NFC_UID = 'B30428070000000000000000000000000000';

    public function run(): void
    {
        $fixedTeacher = User::where('email', 'teacher@checkmate.test')->first();

        if ($fixedTeacher !== null) {
            $this->giveNfcUid($fixedTeacher, self::TEACHER_NFC_UID);
        }

        $teachers = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->when($fixedTeacher !== null, fn ($query) => $query->where('id', '!=', $fixedTeacher->id))
            ->get();

        foreach ($teachers as $teacher) {
            $this->giveNfcUid($teacher, $this->generateNfcUid());
        }

        $students = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))->get();
        $fixedStudent = User::where('email', 'alumno@checkmate.com')->first() ?? $students->first();

        foreach ($students as $student) {
            $this->giveNfcUid(
                $student,
                $student->is($fixedStudent) ? self::STUDENT_NFC_UID : $this->generateNfcUid()
            );
        }
    }

    private function giveNfcUid(User $user, string $nfcUid): void
    {
        UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            ['nfc_uid' => $nfcUid, 'qr_uuid' => (string) Str::uuid()]
        );
    }

    private function generateNfcUid(): string
    {
        return strtoupper(fake()->unique()->regexify('04:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}'));
    }
}
