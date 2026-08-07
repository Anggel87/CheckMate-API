<?php

namespace App\Services\Administrador\Concerns;

use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait SendsWelcomePasswordEmail
{
    /**
     * El correo es un canal adicional, no la única forma de enterarse de la
     * contraseña temporal (ya viaja en la respuesta del POST). Si SMTP falla
     * (mal configurado, proveedor caído), no se debe tumbar la creación del
     * usuario por eso — se registra el error y se sigue.
     */
    private function sendWelcomeEmail(User $user, string $temporaryPassword): void
    {
        try {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el correo de bienvenida.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
