<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Requests\Profile\UpdateOwnProfileRequest;
use App\Http\Resources\TeacherProfileResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse, ValidatesEvidenceFile;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'academicTutor.activeGroups', 'details']);

        return $this->successResponse('Perfil obtenido correctamente.', new TeacherProfileResource($user));
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
            new TeacherProfileResource($user->load(['role', 'academicTutor.activeGroups', 'details'])),
        );
    }
}
