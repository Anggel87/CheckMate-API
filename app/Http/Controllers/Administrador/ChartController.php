<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Incident;
use App\Models\Justification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * School-wide equivalent of Director\ChartController — same four datasets, but never
 * narrowed to a career's groups since the admin oversees the whole school.
 */
class ChartController extends Controller
{
    use ApiResponse;

    public function general(Request $request): JsonResponse
    {
        return $this->successResponse('Resumen general obtenido correctamente.', $this->generalData($request));
    }

    public function incidents(Request $request): JsonResponse
    {
        return $this->successResponse('Estadísticas de incidentes obtenidas correctamente.', $this->incidentsData());
    }

    public function absences(Request $request): JsonResponse
    {
        return $this->successResponse('Tendencias de inasistencias obtenidas correctamente.', $this->absencesData($request));
    }

    public function justifications(Request $request): JsonResponse
    {
        return $this->successResponse('Estado de justificantes obtenido correctamente.', $this->justificationsData());
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->successResponse('Resumen de graficas obtenido correctamente.', [
            'general' => $this->generalData($request),
            'incidents' => $this->incidentsData(),
            'absences' => $this->absencesData($request),
            'justifications' => $this->justificationsData(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generalData(Request $request): array
    {
        $totalStudents = User::query()->whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->where('active', true)
            ->count();

        $summary = $this->attendanceQuery($request)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalAttendances = $summary->sum();
        $present = (int) ($summary['PRESENTE'] ?? 0);

        return [
            'total_students' => $totalStudents,
            'attendance_summary' => [
                'PRESENTE' => (int) ($summary['PRESENTE'] ?? 0),
                'RETARDO' => (int) ($summary['RETARDO'] ?? 0),
                'FALTA' => (int) ($summary['FALTA'] ?? 0),
                'JUSTIFICADA' => (int) ($summary['JUSTIFICADA'] ?? 0),
            ],
            'attendance_rate' => $totalAttendances > 0 ? round($present / $totalAttendances, 4) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incidentsData(): array
    {
        $incidents = Incident::query();

        return [
            'total' => (clone $incidents)->count(),
            'by_severity' => (clone $incidents)->selectRaw('severity, count(*) as total')->groupBy('severity')->pluck('total', 'severity'),
            'by_status' => (clone $incidents)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function absencesData(Request $request): array
    {
        $byGroup = $this->attendanceQuery($request)
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

        $bySubject = $this->attendanceQuery($request)
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

        return [
            'by_group' => $byGroup,
            'by_subject' => $bySubject,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function justificationsData(): array
    {
        $summary = Justification::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'by_status' => [
                'PENDIENTE' => (int) ($summary['PENDIENTE'] ?? 0),
                'ACEPTADO' => (int) ($summary['ACEPTADO'] ?? 0),
                'RECHAZADO' => (int) ($summary['RECHAZADO'] ?? 0),
            ],
        ];
    }

    private function attendanceQuery(Request $request): Builder
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        return Attendance::query()
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('registered_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('registered_at', '<=', $date));
    }
}
