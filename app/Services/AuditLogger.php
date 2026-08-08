<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        string $entity,
        int $entityId,
        string $action,
        ?int $performedByUserId,
        ?array $before,
        ?array $after,
    ): void {
        AuditLog::create([
            'entity' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'performed_by_user_id' => $performedByUserId,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
