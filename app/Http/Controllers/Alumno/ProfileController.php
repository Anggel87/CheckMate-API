<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Requests\Profile\UpdateOwnProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse, ValidatesEvidenceFile;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['group.career', 'tutors', 'details']);

        return $this->successResponse('Perfil obtenido correctamente.', new StudentProfileResource($user));
    }

    public function update(UpdateOwnProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $this->assertValidEvidence($request->file('photo'), 3, ['image/jpeg', 'image/png']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }

        $user->update(collect($data)->only(['phone', 'photo'])->all());

        return $this->successResponse(
            'Perfil actualizado correctamente.',
            new StudentProfileResource($user->load(['group.career', 'tutors', 'details'])),
        );
    }
}
