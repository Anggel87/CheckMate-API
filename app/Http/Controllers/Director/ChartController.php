<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Incident;
use App\Models\Justification;
use App\Services\Director\CareerScope;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ChartController extends Controller
{
    use ApiResponse;

    public function general(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $groupIds = $scope->groupIds($careerIds);

        $totalStudents = $scope->studentIds($careerIds)->count();

        $summary = $this->attendanceQuery($request, $groupIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalAttendances = $summary->sum();
        $present = (int) ($summary['PRESENTE'] ?? 0);

        return $this->successResponse('Resumen general obtenido correctamente.', [
            'total_students' => $totalStudents,
            'attendance_summary' => [
                'PRESENTE' => (int) ($summary['PRESENTE'] ?? 0),
                'RETARDO' => (int) ($summary['RETARDO'] ?? 0),
                'FALTA' => (int) ($summary['FALTA'] ?? 0),
                'JUSTIFICADA' => (int) ($summary['JUSTIFICADA'] ?? 0),
            ],
            'attendance_rate' => $totalAttendances > 0 ? round($present / $totalAttendances, 4) : 0,
        ]);
    }

    public function incidents(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $groupIds = $scope->groupIds($careerIds);

        $incidents = Incident::whereHas('schedule', fn ($query) => $query->whereIn('group_id', $groupIds));

        return $this->successResponse('Estadísticas de incidentes obtenidas correctamente.', [
            'total' => (clone $incidents)->count(),
            'by_severity' => (clone $incidents)->selectRaw('severity, count(*) as total')->groupBy('severity')->pluck('total', 'severity'),
            'by_status' => (clone $incidents)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function absences(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $groupIds = $scope->groupIds($careerIds);

        $byGroup = $this->attendanceQuery($request, $groupIds)
            ->where('attendances.status', 'FALTA')
            ->join('schedules', 'schedules.id', '=', 'attendances.schedule_id')
            ->join('groups', 'groups.id', '=', 'schedules.group_id')
            ->selectRaw('groups.id as group_id, groups.grade, groups.section, count(*) as total')
            ->groupBy('groups.id', 'groups.grade', 'groups.section')
            ->get()
            ->map(fn ($row) => [
                'group_id' => $row->group_id,
                'label' => "{$row->grade}-{$row->section}",
                'total' => (int) $row->total,
            ]);

        $bySubject = $this->attendanceQuery($request, $groupIds)
            ->where('attendances.status', 'FALTA')
            ->join('schedules', 'schedules.id', '=', 'attendances.schedule_id')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->selectRaw('subjects.id as subject_id, subjects.name, count(*) as total')
            ->groupBy('subjects.id', 'subjects.name')
            ->get()
            ->map(fn ($row) => [
                'subject_id' => $row->subject_id,
                'name' => $row->name,
                'total' => (int) $row->total,
            ]);

        return $this->successResponse('Tendencias de inasistencias obtenidas correctamente.', [
            'by_group' => $byGroup,
            'by_subject' => $bySubject,
        ]);
    }

    public function justifications(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $groupIds = $scope->groupIds($careerIds);

        $summary = Justification::whereHas('attendance.schedule', fn ($query) => $query->whereIn('group_id', $groupIds))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->successResponse('Estado de justificantes obtenido correctamente.', [
            'by_status' => [
                'PENDIENTE' => (int) ($summary['PENDIENTE'] ?? 0),
                'ACEPTADO' => (int) ($summary['ACEPTADO'] ?? 0),
                'RECHAZADO' => (int) ($summary['RECHAZADO'] ?? 0),
            ],
        ]);
    }

    /**
     * @param  Collection<int, int>  $groupIds
     */
    private function attendanceQuery(Request $request, Collection $groupIds): Builder
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        return Attendance::whereHas('schedule', fn ($query) => $query->whereIn('group_id', $groupIds))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('registered_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('registered_at', '<=', $date));
    }
}
