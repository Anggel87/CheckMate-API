<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permisos por rol, extraidos de las rutas reales de CheckMate-Frontend
     * (src/app/roles/*\/*.routes.ts). Un rol solo ve las paginas para las que
     * tiene permiso aqui.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'alumno' => [
            'dashboard.view', 'profile.view', 'schedule.view', 'subjects.view',
            'attendance.view', 'justifications.view', 'claims.view',
            'notifications.view', 'emergencies.view', 'settings.view',
        ],
        'profesor' => [
            'dashboard.view', 'profile.view', 'schedule.view', 'groups.view',
            'students.view', 'attendance.view', 'incidents.view', 'claims.view',
            'justifications.view', 'statistics.view', 'notifications.view', 'settings.view',
        ],
        'tutor_academico' => [
            'dashboard.view', 'profile.view', 'schedule.view', 'groups.view', 'students.view',
            'attendance.view', 'alerts.view', 'claims.view', 'justifications.view',
            'statistics.view', 'notifications.view', 'emergencies.view', 'settings.view',
        ],
        'administrador' => [
            'dashboard.view', 'profile.view', 'users.view', 'students.view', 'teachers.view',
            'careers.view', 'groups.view', 'subjects.view', 'schedules.view',
            'academic-periods.view', 'attendance.view', 'attendance-settings.view',
            'classrooms.view', 'nfc-devices.view', 'nfc-cards.view', 'claims.view', 'justifications.view',
            'reports.view', 'statistics.view', 'notifications.view', 'emergencies.view',
            'incidents.view', 'audit.view', 'settings.view',
        ],
        'director_carrera' => [
            'dashboard.view', 'profile.view', 'students.view', 'teachers.view',
            'groups.view', 'subjects.view', 'schedules.view', 'attendance.view',
            'nfc-devices.view', 'incidents.view', 'audit.view',
            'claims.view', 'justifications.view', 'reports.view', 'statistics.view',
            'notifications.view', 'emergencies.view', 'settings.view',
        ],
    ];

    /** @var array<string, string> */
    private const PERMISSION_LABELS = [
        'dashboard.view' => 'Ver panel principal',
        'profile.view' => 'Ver perfil propio',
        'schedule.view' => 'Ver horario',
        'schedules.view' => 'Ver horarios',
        'subjects.view' => 'Ver materias',
        'attendance.view' => 'Ver asistencias',
        'attendance-settings.view' => 'Ver configuracion de asistencia',
        'justifications.view' => 'Ver justificantes',
        'claims.view' => 'Ver reclamos',
        'notifications.view' => 'Ver notificaciones',
        'emergencies.view' => 'Ver emergencias',
        'alerts.view' => 'Ver alertas',
        'settings.view' => 'Ver configuracion',
        'groups.view' => 'Ver grupos',
        'students.view' => 'Ver alumnos',
        'incidents.view' => 'Ver incidentes',
        'statistics.view' => 'Ver estadisticas',
        'users.view' => 'Ver usuarios',
        'teachers.view' => 'Ver profesores',
        'careers.view' => 'Ver carreras',
        'classrooms.view' => 'Ver salones',
        'academic-periods.view' => 'Ver periodos academicos',
        'nfc-devices.view' => 'Ver dispositivos NFC',
        'nfc-cards.view' => 'Ver tarjetas NFC',
        'reports.view' => 'Ver reportes',
        'audit.view' => 'Ver auditoria',
    ];

    public function run(): void
    {
        $permissionModels = collect(self::ROLE_PERMISSIONS)
            ->flatten()
            ->unique()
            ->mapWithKeys(fn (string $keyName) => [
                $keyName => Permission::updateOrCreate(
                    ['key_name' => $keyName],
                    ['name' => self::PERMISSION_LABELS[$keyName] ?? $keyName, 'is_active' => true],
                ),
            ]);

        foreach (self::ROLE_PERMISSIONS as $roleName => $keyNames) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $group = PermissionGroup::updateOrCreate(
                ['key_name' => "{$roleName}.full"],
                ['name' => "Acceso completo - {$roleName}", 'is_active' => true],
            );

            $group->permissions()->sync(
                collect($keyNames)->map(fn (string $keyName) => $permissionModels[$keyName]->id)
            );

            $role->permissionGroups()->syncWithoutDetaching([$group->id]);
        }
    }
}
