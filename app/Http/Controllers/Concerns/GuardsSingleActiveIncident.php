<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use App\Models\Incident;
use App\Services\NotificationService;

trait GuardsSingleActiveIncident
{
    /**
     * Solo puede haber un incidente activo en toda la escuela a la vez, sin importar
     * quien lo haya creado ni en que carrera/grupo — hay que cerrar el que este
     * abierto antes de poder reportar uno nuevo.
     */
    private function assertNoActiveIncidentExists(): void
    {
        if (Incident::where('status', 'ACTIVO')->exists()) {
            throw ApiException::conflict(
                'Ya hay un incidente activo. Debes cerrarlo antes de reportar uno nuevo.',
                'INC04'
            );
        }
    }

    /**
     * Un incidente es una alerta de emergencia (incendio, gas, sismo...) — se avisa a
     * TODA la escuela (tutores por WhatsApp, alumnos y profesores en la app), nunca
     * solo a la carrera/grupo de quien lo reporto.
     */
    private function notifySchoolWideIncident(Incident $incident, int $reportedByUserId, NotificationService $service): void
    {
        $title = $incident->title ?: 'Alerta de emergencia';
        $message = $incident->description ?: "Se reportó un incidente de tipo {$incident->type} en la escuela.";

        $service->broadcastSchoolWide('INCIDENTE', $title, $message, $reportedByUserId);
    }
}
