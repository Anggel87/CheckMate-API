<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Str;

trait AssignsNfcUid
{
    /**
     * Asigna (o reemplaza) el UID de tarjeta NFC de un usuario. Si el usuario todavia
     * no tiene fila en user_details, se crea aqui con un qr_uuid generado, ya que esa
     * columna es unique/NOT NULL y no es responsabilidad de quien llama.
     */
    private function assignNfcUid(User $user, string $nfcUid): void
    {
        $conflict = UserDetail::where('nfc_uid', $nfcUid)
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($conflict !== null) {
            throw ApiException::conflict('Esa tarjeta NFC ya está asignada a otro usuario.', 'NFC01');
        }

        $detail = UserDetail::where('user_id', $user->id)->first();

        if ($detail === null) {
            UserDetail::create([
                'user_id' => $user->id,
                'nfc_uid' => $nfcUid,
                'qr_uuid' => (string) Str::uuid(),
            ]);
        } else {
            $detail->update(['nfc_uid' => $nfcUid]);
        }
    }
}
