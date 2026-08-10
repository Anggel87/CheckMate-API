<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use App\Models\Incident;

trait LoadsIncidentHistory
{
    protected function loadIncidentHistory(Incident $incident): Incident
    {
        $incident->history = AuditLog::where('entity', 'incident')
            ->where('entity_id', $incident->id)
            ->with('performedBy')
            ->oldest()
            ->get()
            ->map(fn (AuditLog $log) => [
                'action' => $log->action,
                'performed_by' => $log->performedBy === null ? null : [
                    'id' => $log->performedBy->id,
                    'full_name' => $log->performedBy->fullName(),
                ],
                'before' => $log->before,
                'after' => $log->after,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->values();

        return $incident;
    }
}
