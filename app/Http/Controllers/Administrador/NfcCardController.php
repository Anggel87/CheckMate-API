<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AssignsNfcUid;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\SetNfcUidRequest;
use App\Http\Resources\AdminNfcCardResource;
use App\Models\User;
use App\Models\UserDetail;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfcCardController extends Controller
{
    use ApiResponse, AssignsNfcUid;

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['role', 'details'])
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('first_surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->query('role_id'), fn ($query, $roleId) => $query->where('role_id', $roleId))
            ->when($request->has('has_card'), fn ($query) => $request->boolean('has_card')
                ? $query->whereHas('details', fn ($query) => $query->whereNotNull('nfc_uid'))
                : $query->where(fn ($query) => $query->doesntHave('details')
                    ->orWhereHas('details', fn ($query) => $query->whereNull('nfc_uid'))))
            ->when($request->has('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->get();

        return $this->successResponse('Usuarios obtenidos correctamente.', AdminNfcCardResource::collection($users));
    }

    /**
     * Asigna (o reemplaza) el UID de tarjeta NFC de un usuario. Si el usuario todavia
     * no tiene fila en user_details (la mayoria no la tiene — hoy solo la crea el
     * seeder de demo), se crea aqui con un qr_uuid generado, ya que la columna es
     * unique/NOT NULL y no es responsabilidad de esta pantalla.
     */
    public function update(SetNfcUidRequest $request, int $user): JsonResponse
    {
        $model = $this->findUser($user);
        $data = $request->validated();

        $this->assignNfcUid($model, $data['nfc_uid']);

        return $this->successResponse('Tarjeta NFC asignada correctamente.', new AdminNfcCardResource($model->load(['role', 'details'])));
    }

    /**
     * Quita la tarjeta asignada sin borrar la fila (conserva el qr_uuid).
     */
    public function destroy(int $user): JsonResponse
    {
        $model = $this->findUser($user);

        UserDetail::where('user_id', $model->id)->update(['nfc_uid' => null]);

        return $this->successResponse('Tarjeta NFC removida correctamente.', new AdminNfcCardResource($model->load(['role', 'details'])));
    }

    private function findUser(int $id): User
    {
        $user = User::find($id);

        if ($user === null) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        return $user;
    }
}
