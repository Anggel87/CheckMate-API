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

> Reemplaza la tabla anterior (obsoleta): esta app **no tiene login propio ni Sanctum**.
> La identidad vive en gobernanza (ver `GOVERNANCE_AUTH.md`); este módulo es el puente
> hacia ella, no un sistema de auth independiente.

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 4.1 | `Auth\AuthController` (createUser, login, popup, callback) | ✅ | Antes vivía en `Controllers/Test/GovernanceTestController`, promovido a oficial |
| 4.2 | `POST /api/v1/auth/users` | ✅ | Crea la identidad en gobernanza (`POST {governance}/internal/users`) |
| 4.3 | `POST /api/v1/auth/login` | ✅ | Proxy a `POST {governance}/auth/login`. Evita exponer `X-Client-Secret` en el frontend |
| 4.4 | `GET /auth/popup`, `GET /auth/callback` | ✅ | En `routes/web.php`, fuera de `/api/v1` — el navegador navega directo, no son llamadas JSON. `redirect_uri` default = `GOVERNANCE_WEB_CALLBACK_URL`, debe coincidir exacto con `CHECKMATE_WEB_CALLBACK_URL` del `.env` de gobernanza o gobernanza rechaza con "acceso no autorizado" |
| 4.5 | `governance.auth` (`ResolveGovernanceUser`) + `role` (`EnsureUserHasRole`) | ✅ | Construidos como parte del Módulo 8 (Alumno), documentados ahí. Se usan en todos los módulos protegidos |
| 4.6 | Tests de Auth | ⏳ | Los middleware sí están cubiertos indirectamente por los tests de Alumno/Profesor/Tutor; falta un `tests/Feature/Auth/*` que pruebe `AuthController` en sí (createUser/login contra gobernanza mockeada) |

**Pendiente/riesgo a revisar:** `POST /api/v1/auth/users` no tiene ninguna protección —
cualquiera puede crear identidades en gobernanza llamando a este endpoint. Cuando se
construya el flujo real de "un admin crea un profesor/alumno/director", hay que decidir
si este endpoint se protege (ej. solo `role:administrator`) o si se vuelve interno y las
altas de actores lo llaman desde dentro de la app, no como endpoint público.

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

## Módulo 7 — Profesor y Tutor Académico

> Reemplaza la tabla anterior (obsoleta): implementado según `CLAUDE_CONTEXT.md` §8.1
> (Profesor) y §8.2 (Tutor Académico), no según el borrador original de este tracker.
> El tutor académico reutiliza los controladores de Profesor vía middleware
> `role:profesor,tutor_academico`; solo sus 3 endpoints exclusivos viven bajo `/tutor`.

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 7.1 | Migraciones nuevas: `justifications.reviewed_by_user_id/reviewed_at/comment`, `claims.action_by_user_id/action_at/comment` + `claims.status` ampliado, `class_sessions.date` + único `(schedule_id, date)` | ✅ | `claims.status` pasó de ENUM a `string(20)` para poder ampliar valores sin `doctrine/dbal` (no instalado) ni SQL no portable entre MySQL/SQLite; ver migraciones `2026_08_03_*` |
| 7.2 | `GET /api/v1/profesor/groups`, `GET /api/v1/profesor/groups/{id}/students` | ✅ | `GroupController` |
| 7.3 | `GET /api/v1/profesor/students/{id}[/attendance][/justifications]` | ✅ | `StudentController`. Asistencia y justificantes se filtran también por `schedule.teacher_id` (el profesor no ve materias que no imparte a ese alumno) |
| 7.4 | `GET /api/v1/profesor/schedule/today`, `GET /api/v1/profesor/schedule` | ✅ | `ScheduleController`, `Support\DayOfWeek` |
| 7.5 | `POST /api/v1/profesor/sessions/open[/{id}/nfc][/{id}/students/{sid}][/{id}/close]` | ✅ | `SessionController` + `OpenClassSessionService`/`RegisterNfcAttendanceService`/`CloseClassSessionService`. Tolerancia PRESENTE/RETARDO calculada contra `attendance_settings` (default 10/30 min si no hay fila) |
| 7.6 | `GET/POST/PUT /api/v1/profesor/incidents[...]`, `PATCH .../students` | ✅ | `IncidentController`. `IncidentFactory` nuevo (no existía) |
| 7.7 | `GET /api/v1/profesor/claims[/{id}]` (solo lectura) | ✅ | `ClaimController` |
| 7.8 | `GET/PATCH /api/v1/tutor/claims[...]`, `PATCH /api/v1/tutor/students/{id}/justifications/{jid}` | ✅ | `Tutor\ClaimController`, `Tutor\JustificationController` + `Services\Tutor\*`. Aprobar un justificante sincroniza `attendance.status = JUSTIFICADA` |
| 7.9 | Tests Pest (`tests/Feature/Profesor/*`, `tests/Feature/Tutor/*`) | ✅ | 40 tests nuevos (61 en la suite completa junto con Alumno). Helpers nuevos en `tests/Pest.php`: `makeTeacherWithSchedule()`, `makeTutorForGroup()`, `makeOpenClassSession()` |

**Limitaciones conocidas:**
- **Notificaciones automáticas al cerrar sesión** (regla de negocio §9.7 del `.md`: avisar a
  tutores familiares cuando un alumno queda `FALTA`) no están implementadas — el Módulo 10
  (Tutores y Notificaciones) todavía no existe. `CloseClassSessionService` sí marca `FALTA`
  correctamente, solo no dispara el aviso.
- **`incidents.schedule_id` es NOT NULL pero `POST /profesor/incidents` no recibe un
  `schedule_id`** en el `.md` (un incidente puede afectar a varios `group_ids`). Se usa como
  ancla el horario activo del profesor en el primer grupo indicado (o cualquier horario
  activo suyo si no manda `group_ids`). Revisar si el modelo de datos debería permitir
  incidentes sin horario o con múltiples horarios.
- **`incidents.reviewed_by_user_id` es NOT NULL** pero conceptualmente se llena cuando
  alguien *revisa* el incidente, no al crearlo. Se inicializa con el propio reportero hasta
  que exista un flujo de revisión (Director de Carrera, §8.5) que lo reasigne.
- **SES01 (sesión duplicada) se garantiza con un índice único `(schedule_id, date)`** en vez
  de solo estados `ABIERTA/CERRADA` — significa que si una sesión se cancela (`CANCELADA`)
  no se puede abrir otra el mismo día para ese horario sin intervención manual.
- **`claims.tutor_id` sigue guardando al alumno que reclama**, no un tutor familiar (mismo
  supuesto ya documentado en el Módulo 8/Alumno). Las vistas de Profesor/Tutor lo exponen
  como `student`, no como `tutor`.
- **`TutorClaimResource.history` siempre es `[]`**: no existe una tabla de auditoría de
  acciones sobre reclamos, solo la última acción (`action_by_user_id`/`action_at`/`comment`).

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
| 8.9 | Aprobación de justificantes (tutor académico, §8.2) | ✅ | Migración y flujo de aprobación se construyeron en el Módulo 7 (`Tutor\JustificationController`). `JustificationResource` de Alumno actualizado para mostrar `reviewed_by`/`reviewed_at`/`comment` reales (antes los devolvía hardcodeados en `null`) |
| 8.10 | Comando `governance:link-students` | ✅ | Crea en gobernanza los alumnos sembrados sin `governance_user_id` y los enlaza. Opcional, no forma parte de `migrate:fresh --seed` porque requiere gobernanza corriendo |
| 8.11 | `AttendanceSeeder` | ✅ | Sí forma parte de `migrate:fresh --seed` (no depende de servicios externos). Un horario por grupo, una sesión pasada por horario, asistencia por alumno del grupo (~20 registros totales, mezcla `PRESENTE`/`RETARDO`/`FALTA`) |

**Limitaciones conocidas (ver plan de implementación para detalle):**
- `claims.tutor_id` se usa como "quien presenta el reclamo" (el alumno mismo aquí); no
  hay `attendance_id` en el body del `.md`, así que se toma la asistencia más reciente
  del alumno en esa materia — revisar si el alumno familiar tutor comparte este flujo.
- No hay código de catálogo para "token de gobernanza válido sin usuario local
  vinculado" (403 sin `error_code` en `ResolveGovernanceUser`).
- `fakeGovernanceAuth()` en `tests/Pest.php` ahora guarda un registro token→usuario en
  el contenedor en vez de que cada llamada pise la anterior. Llamarlo varias veces en un
  mismo test (para simular dos roles distintos, ej. tutor revisa → alumno lee) requiere
  pasar un `$token` distinto cada vez; con el mismo token siempre resuelve al último
  usuario registrado para ese token, tal como lo haría el cache real de
  `ResolveGovernanceUser`.

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
   `routes/api.php` (que solo orquesta `require`s), todo bajo `Route::prefix('v1')`. Las
   rutas que el navegador navega directo (no llamadas JSON, ej. el popup/callback de
   gobernanza) van en `routes/web.php`, fuera de `/api`, porque su URL debe coincidir
   exacto con algo configurado externamente (ver `auth.popup`/`auth.callback` y la nota
   sobre `CHECKMATE_WEB_CALLBACK_URL` en `GOVERNANCE_AUTH.md`).
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
