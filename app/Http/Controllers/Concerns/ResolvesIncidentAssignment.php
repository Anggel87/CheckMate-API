<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use App\Models\Group;
use App\Models\Incident;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;

trait ResolvesIncidentAssignment
{
    /**
     * Un incidente creado por admin/director no lo pasa de lista quien lo crea, sino el
     * profesor o tutor académico elegido como responsable — se ancla a un horario activo
     * suyo (de preferencia en uno de los grupos afectados) para satisfacer la columna
     * incidents.schedule_id (NOT NULL) y para que las validaciones de propiedad del lado
     * de Profesor (reported_by_user_id) le den acceso a la lista de verificación.
     *
     * @param  array<int, int>  $groupIds
     * @param  Collection<int, int>|null  $allowedGroupIds  Si se pasa (Director), el horario
     *                                                      del responsable debe caer en uno de estos grupos; si no se encuentra ninguno se
     *                                                      trata como "fuera de alcance" (403) en vez de "sin horarios activos" (422).
     */
    private function resolveResponsibleSchedule(int $responsibleUserId, array $groupIds, ?Collection $allowedGroupIds = null): Schedule
    {
        $responsible = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->find($responsibleUserId);

        if ($responsible === null) {
            throw ApiException::unprocessable('El profesor o tutor seleccionado no es válido.', 'VAL04');
        }

        $query = Schedule::where('teacher_id', $responsible->id)->where('is_active', true);

        if ($allowedGroupIds !== null) {
            $query->whereIn('group_id', $allowedGroupIds);
        }

        $schedule = (clone $query)
            ->when(! empty($groupIds), fn ($q) => $q->whereIn('group_id', $groupIds))
            ->first() ?? $query->first();

        if ($schedule === null) {
            if ($allowedGroupIds !== null) {
                throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
            }

            throw ApiException::unprocessable('El profesor o tutor seleccionado no tiene horarios activos.', 'VAL05');
        }

        return $schedule;
    }

    /**
     * @param  array<int, int>  $groupIds
     */
    private function attachIncidentGroups(Incident $incident, array $groupIds, int $checkedByUserId): void
    {
        foreach ($groupIds as $groupId) {
            $group = Group::find($groupId);

            if ($group === null) {
                continue;
            }

            foreach ($group->students()->active()->get() as $student) {
                $incident->students()->attach($student->id, [
                    'status' => 'DESCONOCIDO',
                    'checked_by_user_id' => $checkedByUserId,
                    'checked_at' => now(),
                ]);
            }
        }
    }

    private function defaultIncidentTitle(string $type): string
    {
        $labels = [
            'FIRE' => 'Incendio',
            'GAS' => 'Fuga de gas',
            'EARTHQUAKE' => 'Sismo',
            'OTHER' => 'Incidente',
        ];

        $label = $labels[$type] ?? 'Incidente';

        return $label.' - '.now()->format('d/m/Y H:i');
    }
}
