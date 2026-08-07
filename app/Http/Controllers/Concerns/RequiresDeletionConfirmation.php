<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;

trait RequiresDeletionConfirmation
{
    protected function ensureConfirmed(Request $request): void
    {
        if ($request->boolean('confirm') !== true) {
            throw ApiException::unprocessable('Debes confirmar la acción para continuar.', 'VAL03');
        }
    }
}
