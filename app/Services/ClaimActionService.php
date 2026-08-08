<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Claim;
use App\Models\User;

class ClaimActionService
{
    public function act(User $actor, Claim $claim, string $action, ?string $comment): Claim
    {
        if (in_array($claim->status, ['ACEPTADO', 'RECHAZADO'], true)) {
            throw ApiException::conflict('Esta reclamación ya fue resuelta o rechazada.', 'CLM02');
        }

        $claim->update([
            'status' => $action,
            'action_by_user_id' => $actor->id,
            'action_at' => now(),
            'comment' => $comment,
        ]);

        return $claim->refresh();
    }
}
