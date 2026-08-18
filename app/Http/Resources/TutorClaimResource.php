<?php

namespace App\Http\Resources;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Vista del tutor académico sobre un reclamo: agrega grupo/carrera (ve reclamos de
 * varios grupos a la vez, a diferencia del profesor que ya sabe cuál es su clase).
 *
 * @mixin Claim
 */
class TutorClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->tutor; // claims.tutor_id guarda al alumno que reclama, no a un tutor familiar
        $schedule = $this->attendance?->schedule;
        // Reclamos generales (sin materia) no tienen schedule; se usa el grupo
        // actual del alumno para poder mostrar/filtrar igual por grupo y carrera.
        $group = $schedule?->group ?? $student->group;

        return [
            'id' => $this->id,
            'student' => [
                'id' => $student->id,
                'full_name' => $student->fullName(),
            ],
            'group' => $group ? [
                'id' => $group->id,
                'grade' => $group->grade,
                'section' => $group->section,
            ] : null,
            'career' => $group?->career ? [
                'id' => $group->career->id,
                'name' => $group->career->name,
                'short_name' => $group->career->short_name,
            ] : null,
            'subject' => $schedule ? [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ] : null,
            'description' => $this->description,
            'evidence_url' => $this->evidence ? Storage::disk('public')->url($this->evidence) : null,
            'status' => $this->status,
            // No existe una tabla de auditoría de acciones sobre el reclamo todavía,
            // solo se persiste la ultima accion tomada.
            'last_action' => $this->action_at ? [
                'by' => $this->actionBy?->fullName(),
                'at' => $this->action_at->toIso8601String(),
                'comment' => $this->comment,
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
