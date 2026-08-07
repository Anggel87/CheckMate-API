<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = DB::table('student_tutor')->orderBy('student_id')->limit(2)->get();
        $admin = User::where('email', 'administrador@checkmate.test')->first();

        if ($pairs->isEmpty()) {
            return;
        }

        $first = $pairs->first();

        AppNotification::create([
            'student_id' => $first->student_id,
            'tutor_id' => $first->tutor_id,
            'title' => 'Inasistencia registrada',
            'message' => 'El alumno faltó a su clase de hoy.',
            'type' => 'INASISTENCIA',
            'is_read' => false,
            'sent_at' => now()->subDay(),
        ]);

        AppNotification::create([
            'student_id' => $first->student_id,
            'tutor_id' => $first->tutor_id,
            'title' => 'Retardo registrado',
            'message' => 'El alumno llegó tarde a su clase.',
            'type' => 'RETARDO',
            'is_read' => true,
            'sent_at' => now()->subDays(2),
        ]);

        $second = $pairs->last();

        AppNotification::create([
            'student_id' => $second->student_id,
            'tutor_id' => $second->tutor_id,
            'sent_by_user_id' => $admin?->id,
            'title' => 'Junta de padres de familia',
            'message' => 'La junta será el próximo viernes a las 6pm en el auditorio.',
            'type' => 'AVISO',
            'is_read' => false,
            'sent_at' => now(),
        ]);

        AppNotification::create([
            'student_id' => $second->student_id,
            'tutor_id' => $second->tutor_id,
            'sent_by_user_id' => $admin?->id,
            'title' => 'Suspensión de clases',
            'message' => 'No habrá clases el lunes por mantenimiento del plantel.',
            'type' => 'AVISO',
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }
}
