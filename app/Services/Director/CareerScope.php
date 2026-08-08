<?php

namespace App\Services\Director;

use App\Exceptions\ApiException;
use App\Models\Career;
use App\Models\Device;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Traduce "director de carrera autenticado" a los IDs que puede ver, para que los
 * controladores de App\Http\Controllers\Director no dupliquen la lógica de alcance.
 */
class CareerScope
{
    /**
     * @return Collection<int, int>
     */
    public function careerIds(User $director): Collection
    {
        return Career::where('director_id', $director->id)->pluck('id');
    }

    /**
     * @return Collection<int, int>
     */
    public function assertHasCareer(User $director): Collection
    {
        $careerIds = $this->careerIds($director);

        if ($careerIds->isEmpty()) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $careerIds;
    }

    /**
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, int>
     */
    public function groupIds(Collection $careerIds): Collection
    {
        return Group::whereIn('career_id', $careerIds)->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, int>
     */
    public function studentIds(Collection $careerIds): Collection
    {
        return User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->whereIn('group_id', $this->groupIds($careerIds))
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, int>
     */
    public function teacherIds(Collection $careerIds): Collection
    {
        $groupIds = $this->groupIds($careerIds);

        return User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->whereHas('schedules', fn ($query) => $query->whereIn('group_id', $groupIds))
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, int>
     */
    public function classroomIds(Collection $careerIds): Collection
    {
        return Schedule::whereIn('group_id', $this->groupIds($careerIds))
            ->where('is_active', true)
            ->pluck('classroom_id')
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, int>
     */
    public function deviceIds(Collection $careerIds): Collection
    {
        return Device::whereIn('classroom_id', $this->classroomIds($careerIds))->pluck('id');
    }
}
