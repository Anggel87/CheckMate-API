# CheckMate API — Progress Tracker

> Checklist de módulos, tareas, estado y notas de desarrollo.
> Estados: ✅ Completo | 🔄 En progreso | ⏳ Pendiente | 🐛 Bug / Incompleto | ⚠️ Revisar

> ⚠️ **`CLAUDE_CONTEXT.md` es la fuente de verdad técnica actual** (esquema, endpoints,
> catálogo de errores). Varias secciones de este tracker (nombres de rol, tablas
> `teachers`/`students`/`directors` separadas, endpoints en `mis-` en vez de recursos
> REST) quedaron desactualizadas respecto al modelo RBAC puro ya migrado. Antes de dar
> por buena una fila de aquí, verifica contra `CLAUDE_CONTEXT.md` y el esquema real con
> la herramienta `database-schema` de Boost.

---

## Convenio de nombres — TODO EN INGLÉS (código) / ESPAÑOL (rutas y respuestas API)

**Regla principal:** Tablas, columnas, modelos, variables, factories y migraciones van en inglés. Las rutas (`/api/v1/alumnos`) y los campos que la API recibe o devuelve van en español.

### Tablas de la aplicación

| Modelo | Tabla |
|--------|-------|
| `Role` | `roles` |
| `User` | `users` |
| `Director` | `directors` |
| `Teacher` | `teachers` |
| `Student` | `students` |
| `Tutor` | `tutors` |
| `AcademicTutor` | `academic_tutors` |
| (futuros) | en inglés siempre |

### Columnas de la tabla `users`

| Columna | Descripción |
|---------|-------------|
| `roles_id` | FK a tabla `roles` |
| `name` | Nombre completo del usuario |
| `email` | Email (único) |
| `password` | Contraseña hasheada |
| `verified_at` | Verificación de email |
| `active` | Estado del usuario |
| `remember_token` | Columna interna de Laravel |

### Columnas FK — convención plural

- FK usa el nombre de la tabla de destino + `_id`: `users_id`, `teachers_id`, `students_id`, `tutors_id`, `roles_id`, etc.
- Ejemplo: la FK de `teachers` hacia `users` se llama `users_id`, no `user_id`.

### Excepciones justificadas (tablas/columnas del framework)

| Tabla / Columna | Razón |
|-----------------|-------|
| `cache` | Laravel internal |
| `jobs`, `failed_jobs` | Laravel Queue |
| `sessions` | Laravel Session driver |
| `password_reset_tokens` | Laravel Password Broker |
| `personal_access_tokens` | Sanctum |
| `remember_token` (columna) | Laravel `Authenticatable` trait |

### Roles del sistema

Los roles viven en la tabla `roles` (no enum). Seeder: `RoleSeeder`.

| Nombre DB | Tabla de perfil | Descripción |
|-----------|-----------------|-------------|
| `ADMIN` | ninguna — solo `users` | Operador del sistema, acceso total |
| `DIRECTOR` | `directors` | Director escolar |
| `TEACHER` | `teachers` | Docente del plantel |
| `STUDENT` | `students` | Alumno del plantel |

### Auth (columnas estándar de Laravel)

`User` usa `email` y `password` nativos — no requiere overrides de auth. `Auth::attempt()` normal:

```php
Auth::attempt(['email' => $request->email, 'password' => $request->password])
```

---

## Módulo 0 — Fundación y configuración base

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 0.1 | Proyecto Laravel 12 inicializado | ✅ | |
| 0.2 | Tabla `roles` + Modelo `Role` + `RoleSeeder` | ✅ | Reemplaza enum. `app/Models/Role.php` |
| 0.3 | Trait `ApiResponse` | ✅ | `app/Traits/ApiResponse.php` — success, error, paginated |
| 0.4 | Migración `users` con FK `roles_id`, `active`, `verified_at` | ✅ | |
| 0.5 | Modelo `User` con relaciones y HasApiTokens | ✅ | Relaciones a student, teacher, director, academicTutor |
| 0.6 | `UserFactory` con estados (admin, director, teacher, student, inactive) | ✅ | Usa `Role::firstOrCreate` |

---

## Módulo 1 — Los 6 actores del sistema

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 1.1 | Migración `teachers` | ✅ | FK `users_id` unique, `speciality` |
| 1.2 | Modelo `Teacher` | ✅ | Relaciones: user, academicTutor |
| 1.3 | `TeacherFactory` | ✅ | Estados: inactive |
| 1.4 | Migración `students` | ✅ | FK `users_id` nullable, `student_number`, `gender ENUM('M','F','OTRO')` |
| 1.5 | Modelo `Student` | ✅ | Relaciones: user, tutors (BelongsToMany) |
| 1.6 | `StudentFactory` | ✅ | Estados: withNfc, inactive, withoutAccount |
| 1.7 | Migración `tutors` | ✅ | Sin login. Sin relationship/receives_notifications (van al pivote) |
| 1.8 | Modelo `Tutor` | ✅ | Relación students (BelongsToMany) |
| 1.9 | `TutorFactory` | ✅ | Estados: inactive |
| 1.10 | Migración `academic_tutors` | ✅ | FK `teachers_id`, sin SoftDeletes |
| 1.11 | Modelo `AcademicTutor` | ✅ | Relación teacher + groups (pendiente FK real) |
| 1.12 | `AcademicTutorFactory` | ✅ | |
| 1.13 | Migración `student_tutor` (pivote) | ✅ | `relationship`, `is_primary`, `receives_notifications` |
| 1.14 | Migración `directors` | ✅ | FK `users_id` unique |
| 1.15 | Modelo `Director` | ✅ | Relaciones: user |
| 1.16 | `DirectorFactory` | ✅ | Estados: inactive |

> **Decisión de diseño:** El tutor académico siempre es un docente. No existe rol `TUTOR_ACADEMICO` en `users`.
> **Decisión de diseño:** El administrador no tiene tabla de perfil. El Director sí tiene tabla `directors`.
> **Decisión de diseño (MER v2):** FKs usan nombre de tabla destino en plural: `users_id`, `teachers_id`, `students_id`, `tutors_id`, `roles_id`.

---

## Módulo 2 — Ciclos escolares y grupos

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 2.1 | Migración `school_years` | ✅ | status ENUM('ACTIVE','INACTIVE','FINISHED'), is_active |
| 2.2 | Modelo `SchoolYear` + `SchoolYearFactory` | ✅ | Estados: active, finished |
| 2.3 | Migración `groups` | ✅ | FK `school_years_id`, unique(school_years_id, name), shift ENUM |
| 2.4 | Modelo `Group` + `GroupFactory` | ✅ | Estados: inactive |
| 2.5 | FK `groups_id` en `students` | ✅ | Agregada en migración de groups, nullOnDelete |
| 2.6 | Migración `grupo_tutor_academico` (pivote) | ⏳ | FK school_years_id, is_active, assigned_at |

---

## Módulo 3 — Materias, horarios y configuración de asistencia

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 3.1 | Migración `subjects` | ✅ | name, code UNIQUE, description, is_active |
| 3.2 | Modelo `Subject` + `SubjectFactory` | ✅ | Estados: inactive |
| 3.3 | Migración `schedules` | ✅ | FK: school_years_id, groups_id, teachers_id, subjects_id. Unique compuesto. day_of_week ENUM |
| 3.4 | Modelo `Schedule` + `ScheduleFactory` | ✅ | Estados: inactive |
| 3.5 | Migración `attendance_settings` | ⏳ | FK schedules_id. Tolerancias y reglas por horario |
| 3.6 | Modelo `AttendanceSettings` | ⏳ | |

---

## Módulo 4 — Autenticación

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 4.1 | `AuthController` (login, logout, me) | ⏳ | Sanctum. Verificar is_active al login |
| 4.2 | Form Request `LoginRequest` | ⏳ | Validaciones en español |
| 4.3 | Resource `UserResource` | ⏳ | No exponer password ni remember_token |
| 4.4 | Rutas `/api/v1/auth/*` | ⏳ | |
| 4.5 | Middleware `active.user` | ⏳ | Bloquear usuarios con is_active = false |
| 4.6 | Middleware `role` | ⏳ | Restricción por rol, ej: role:ADMIN,DOCENTE |
| 4.7 | Tests de autenticación | ⏳ | Login exitoso, login inactivo, token inválido |

---

## Módulo 5 — Dispositivos Raspberry Pi 4B

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 5.1 | Migración `dispositivos` | ⏳ | api_token_hash, mac_address único, aula_id |
| 5.2 | Modelo `Dispositivo` | ⏳ | |
| 5.3 | `DispositivoController` | ⏳ | CRUD + regenerar token |
| 5.4 | `DeviceAuthService` | ⏳ | Validar token hash + MAC + activo |
| 5.5 | Middleware `device.auth` | ⏳ | Para rutas usadas por Raspberry |
| 5.6 | Endpoint `POST /device/heartbeat` | ⏳ | Actualiza ultimo_contacto_at |

---

## Módulo 6 — Registro de asistencia NFC

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 6.1 | Migración `asistencias` | ⏳ | Índice único: alumno_id + horario_id + fecha |
| 6.2 | Modelo `Asistencia` | ⏳ | Enum estado: PRESENTE, RETARDO, FALTA, JUSTIFICADA |
| 6.3 | `AttendanceWindowService` | ⏳ | Calcula estado según configuración del horario |
| 6.4 | `AttendanceRegistrationService` | ⏳ | Orquesta validación + registro + eventos |
| 6.5 | `DeviceAttendanceController` | ⏳ | `POST /device/asistencias/nfc` |
| 6.6 | Form Request `NfcAttendanceRequest` | ⏳ | |
| 6.7 | Evento `AttendanceRegistered` | ⏳ | |
| 6.8 | Listener `WriteAttendanceAuditLog` | ⏳ | |
| 6.9 | Tests de flujo NFC | ⏳ | Presente, retardo, falta, duplicado, NFC no registrado |

---

## Módulo 7 — Panel docente

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 7.1 | `GET /docente/mis-horarios` | ⏳ | |
| 7.2 | `GET /docente/clase-activa` | ⏳ | |
| 7.3 | `GET /docente/horarios/{id}/asistencias-hoy` | ⏳ | |
| 7.4 | `POST /docente/asistencias/{id}/justificar` | ⏳ | |
| 7.5 | `JustificationService` | ⏳ | Cambia estado a JUSTIFICADA |
| 7.6 | Migración `justificaciones` | ⏳ | |
| 7.7 | Modelo `Justificacion` | ⏳ | |

---

## Módulo 8 — Panel alumno

> Reemplaza la tabla anterior (obsoleta): implementado según `CLAUDE_CONTEXT.md` §8.3,
> no según el borrador original de este tracker.

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 8.1 | `governance_user_id` en `users` + middleware `governance.auth`/`role` | ✅ | `ResolveGovernanceUser`, `EnsureUserHasRole`. Ver `GOVERNANCE_AUTH.md`. `GET /auth/me` se cachea por token (`GOVERNANCE_AUTH_CACHE_TTL`, default 120s) para no llamar a gobernanza en cada request |
| 8.2 | `ApiException` + render de `ValidationException` (VAL01) | ✅ | `app/Exceptions/ApiException.php`, `bootstrap/app.php` |
| 8.3 | `GET /api/v1/alumno/profile` | ✅ | `ProfileController` |
| 8.4 | `GET\|POST /api/v1/alumno/claims`, `GET /api/v1/alumno/claims/{id}` | ✅ | `ClaimController`. PERM02 si la materia no es del alumno |
| 8.5 | `GET /api/v1/alumno/justifications[/{id}]` | ✅ | `JustificationController` |
| 8.6 | `POST /api/v1/alumno/subjects/{id}/attendance/{aid}/justify` | ✅ | `JustifyAttendanceService` (ATT03/ATT04/JUST03) |
| 8.7 | `GET /api/v1/alumno/subjects[/{id}][/attendance]` | ✅ | `SubjectController`, `ScheduleFormatter` |
| 8.8 | Tests Pest (`tests/Feature/Alumno/*`) | ✅ | 21 tests, `Http::fake()` simula `GET {governance}/auth/me` |
| 8.9 | Aprobación de justificantes (tutor académico, §8.2) | ⏳ | Requiere agregar `reviewed_by`/`comment` a `justifications` |
| 8.10 | Comando `governance:link-students` | ✅ | Crea en gobernanza los alumnos sembrados sin `governance_user_id` y los enlaza. Opcional, no forma parte de `migrate:fresh --seed` porque requiere gobernanza corriendo |
| 8.11 | `AttendanceSeeder` | ✅ | Sí forma parte de `migrate:fresh --seed` (no depende de servicios externos). Un horario por grupo, una sesión pasada por horario, asistencia por alumno del grupo (~20 registros totales, mezcla `PRESENTE`/`RETARDO`/`FALTA`) |

**Limitaciones conocidas (ver plan de implementación para detalle):**
- `claims.tutor_id` se usa como "quien presenta el reclamo" (el alumno mismo aquí); no
  hay `attendance_id` en el body del `.md`, así que se toma la asistencia más reciente
  del alumno en esa materia — revisar si el alumno familiar tutor comparte este flujo.
- `justifications` no tiene columnas `reviewed_by`/`comment`; el detalle del alumno
  siempre las devuelve en `null` hasta que se construya el módulo de aprobación.
- No hay código de catálogo para "token de gobernanza válido sin usuario local
  vinculado" (403 sin `error_code` en `ResolveGovernanceUser`).

---

## Módulo 9 — Generación automática de faltas

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 9.1 | `AbsenceGenerationService` | ⏳ | |
| 9.2 | Command `attendance:generate-absences` | ⏳ | Scheduler programado varias veces al día |
| 9.3 | Evento `AbsenceGenerated` | ⏳ | |
| 9.4 | Listener `NotifyTutorAboutAbsence` | ⏳ | |

---

## Módulo 10 — Tutores y notificaciones

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 10.1 | CRUD `tutores` | ⏳ | |
| 10.2 | Migración `alumno_tutor` (pivote) | ⏳ | tipo_responsable, principal |
| 10.3 | Migración `notificaciones` | ⏳ | |
| 10.4 | Migración `preferencias_notificacion` | ⏳ | |
| 10.5 | `NotificationService` | ⏳ | |

---

## Módulo 11 — Incidencias

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 11.1 | Migración `incidencias` | ⏳ | gravedad: BAJA, MEDIA, ALTA, CRITICA |
| 11.2 | Migración `historial_incidencias` | ⏳ | |
| 11.3 | Modelo `Incidencia` | ⏳ | |
| 11.4 | `IncidentService` | ⏳ | |

---

## Módulo 12 — Auditoría y logs

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 12.1 | Migración `auditorias` | ⏳ | accion: CREATE, UPDATE, DELETE, LOGIN, etc. |
| 12.2 | Migración `logs_sistema` | ⏳ | nivel: INFO, WARNING, ERROR, CRITICAL |
| 12.3 | `AuditService` | ⏳ | |
| 12.4 | Command `logs:clean` | ⏳ | |

---

## Módulo 13 — Reportes

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 13.1 | `GET /reportes/asistencias` | ⏳ | Filtros: grupo, alumno, materia, fecha |
| 13.2 | `GET /reportes/faltas` | ⏳ | |
| 13.3 | `GET /reportes/retardos` | ⏳ | |
| 13.4 | `GET /reportes/alumnos-riesgo` | ⏳ | Alumnos con faltas recurrentes |
| 13.5 | `ReportService` | ⏳ | |
| 13.6 | `StatisticsService` | ⏳ | |

---

## Módulo 14 — CRUDs administrativos

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.1 | CRUD usuarios (`/api/v1/usuarios`) | ⏳ | Con Policies |
| 14.2 | CRUD alumnos (`/api/v1/alumnos`) | ⏳ | PATCH nfc, GET asistencias |
| 14.3 | CRUD docentes (`/api/v1/docentes`) | ⏳ | GET horarios |
| 14.4 | CRUD grupos (`/api/v1/grupos`) | ⏳ | GET alumnos del grupo |
| 14.5 | CRUD materias (`/api/v1/materias`) | ⏳ | |
| 14.6 | CRUD aulas (`/api/v1/aulas`) | ⏳ | |
| 14.7 | CRUD horarios (`/api/v1/horarios`) | ⏳ | Validar traslapes |
| 14.8 | CRUD ciclos escolares (`/api/v1/ciclos-escolares`) | ⏳ | Solo un activo a la vez |

---

## Decisiones de diseño registradas

1. **Tutor académico siempre es docente** — No existe rol `TUTOR_ACADEMICO`. La diferencia está en tener registro en `academic_tutors` con FK `teachers_id`.
2. **Tutor familiar sin login** — `tutors` no tiene `users_id`. El diseño permite agregarlo en el futuro.
3. **group_id en students sin FK** — FK se agrega en la migración de groups para respetar el orden.
4. **Roles en tabla separada** — La tabla `roles` almacena ADMIN, DIRECTOR, TEACHER, STUDENT. Seeder: `RoleSeeder`. FK `roles_id` en `users`.
5. **FKs en plural** — Convención del MER: `users_id`, `teachers_id`, `students_id`, `tutors_id`, `roles_id`.
6. **relationship y receives_notifications en pivote** — Estos datos viven en `student_tutor`, no en `tutors`.
7. **API responses** — Trait `ApiResponse` en todos los controladores.
8. **Validaciones en español** — Todos los Form Requests con mensajes en español.

---

## Convención de trabajo por módulos

A partir del Módulo 8 (Alumno), cada módulo nuevo se construye así:

1. **Antes de codear:** leer la sección `§8.x` correspondiente en `CLAUDE_CONTEXT.md`
   **y** verificar el esquema real con la herramienta `database-schema` de Boost. El
   `.md` puede desactualizarse respecto a las migraciones (ya pasó con los nombres de
   rol: el seeder usa `administrador`/`director_carrera`, el `.md` dice
   `administrator`/`career_director`).
2. **Rutas:** un archivo por módulo en `routes/api/{modulo}.php`, incluido desde
   `routes/api.php` (que solo orquesta `require`s). Todas las rutas de negocio van bajo
   `Route::prefix('v1')`; las rutas de prueba (`test/governance/...`) se quedan fuera de
   `v1` en `routes/api/test.php`.
3. **Controladores:** carpeta por módulo en `app/Http/Controllers/{Modulo}/`, un
   controlador por agrupación de recursos del `.md` (no uno gigante por rol).
4. **Compartido entre módulos** (middleware, `ApiException`, `GovernanceClient`,
   helpers como `ScheduleFormatter`) vive en las carpetas raíz (`app/Http/Middleware`,
   `app/Exceptions`, `app/Services/Governance`, `app/Support`), nunca duplicado por
   módulo.
5. **Requests** por módulo en `app/Http/Requests/{Modulo}/`. **Resources** compartidos
   en la raíz de `app/Http/Resources/` (representan entidades, no vistas por rol, y se
   reutilizan entre módulos que exponen el mismo dato, ej. `Claim`, `Justification`).
6. **Servicios** solo cuando hay lógica de negocio real (validaciones encadenadas,
   efectos secundarios), en `app/Services/{Modulo}/`. Si es una query simple, va
   directo en el controlador.
7. **Enums en las respuestas:** siempre el valor tal cual está en la BD (español:
   `PRESENTE`, `PENDIENTE`, `ACTIVO`...), aunque los ejemplos ilustrativos de
   `CLAUDE_CONTEXT.md` usen tokens en inglés.
8. **Errores:** usar `ApiException::notFound()/forbidden()/conflict()/...` con el
   código del catálogo (sección 7 de `CLAUDE_CONTEXT.md`) cuando exista uno; si no
   existe un código para el caso, usar el status HTTP correcto sin forzar un código
   incorrecto, y anotarlo como pendiente en este tracker.
9. **Tests:** Pest en `tests/Feature/{Modulo}/`, uno por controlador. Usar
   `fakeGovernanceAuth($user)` (helper en `tests/Pest.php`) para simular
   `GET {governance}/auth/me` y autenticar como cualquier usuario local sin gobernanza
   real corriendo. `RefreshDatabase` está activo globalmente para `tests/Feature`.
10. Marcar las filas del módulo en este tracker conforme se completen, y anotar
    limitaciones/supuestos (columnas faltantes, ambigüedades del `.md`) en una nota
    dentro de la sección del módulo, no solo en el chat.
