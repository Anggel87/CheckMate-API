<?php

namespace App\Services\Tutor;

use App\Exceptions\ApiException;
use App\Models\Claim;
use App\Models\User;

class ClaimActionService
{
    public function act(User $tutor, Claim $claim, string $action, ?string $comment): Claim
    {
        if (in_array($claim->status, ['ACEPTADO', 'RECHAZADO'], true)) {
            throw ApiException::conflict('Esta reclamación ya fue resuelta o rechazada.', 'CLM02');
        }

        $claim->update([
            'status' => $action,
            'action_by_user_id' => $tutor->id,
            'action_at' => now(),
            'comment' => $comment,
        ]);

        return $claim->refresh();
    }
}
