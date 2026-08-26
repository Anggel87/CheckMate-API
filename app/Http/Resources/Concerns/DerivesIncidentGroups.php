<?php

namespace App\Http\Resources\Concerns;

use App\Models\Group;
use App\Models\Incident;

/** @mixin Incident */
trait DerivesIncidentGroups
{
    /**
     * Los grupos afectados son los de los alumnos en la lista de verificacion — puede haber
     * varios si el incidente se creo con group_ids multiples (flujo de administrador/director).
     * Si todavia no hay alumnos en la lista (ej. incidente reportado sin group_ids), se usa el
     * grupo del horario ancla como respaldo, igual que mostraba el detalle antes de soportar
     * multiples grupos.
     *
     * @return list<array{id: int, grade: string, section: string}>
     */
    private function affectedGroups(): array
    {
        $studentGroups = $this->students
            ->map(fn ($student) => $student->group)
            ->filter()
            ->unique('id')
            ->values();

        if ($studentGroups->isEmpty()) {
            $group = $this->schedule->group;

            return $group ? [$this->groupPayload($group)] : [];
        }

        return $studentGroups->map(fn (Group $group) => $this->groupPayload($group))->all();
    }

    /**
     * @return array{id: int, grade: string, section: string}
     */
    private function groupPayload(Group $group): array
    {
        return [
            'id' => $group->id,
            'grade' => $group->grade,
            'section' => $group->section,
        ];
    }
}
