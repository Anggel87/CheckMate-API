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
| 4.2 | `POST /api/v1/auth/users` | ✅ | Crea la identidad en gobernanza (`POST {governance}/internal/users`). Protegido con `governance.auth` + `role:administrador` (ver nota de abajo, ya resuelta) |
| 4.3 | `POST /api/v1/auth/login` | ✅ | Proxy a `POST {governance}/auth/login`. Evita exponer `X-Client-Secret` en el frontend |
| 4.4 | `GET /auth/popup`, `GET /auth/callback` | ✅ | En `routes/web.php`, fuera de `/api/v1` — el navegador navega directo, no son llamadas JSON. `redirect_uri` default = `GOVERNANCE_WEB_CALLBACK_URL`, debe coincidir exacto con `CHECKMATE_WEB_CALLBACK_URL` del `.env` de gobernanza o gobernanza rechaza con "acceso no autorizado" |
| 4.5 | `governance.auth` (`ResolveGovernanceUser`) + `role` (`EnsureUserHasRole`) | ✅ | Construidos como parte del Módulo 8 (Alumno), documentados ahí. Se usan en todos los módulos protegidos |
| 4.6 | Tests de Auth | ⏳ | Los middleware sí están cubiertos indirectamente por los tests de Alumno/Profesor/Tutor; falta un `tests/Feature/Auth/*` que pruebe `AuthController` en sí (createUser/login contra gobernanza mockeada) |

**Resuelto:** `POST /api/v1/auth/users` estuvo sin protección varias sesiones (cualquiera
podía crear identidades en gobernanza). El usuario decidió protegerlo ya con
`['governance.auth', 'role:administrador']` en vez de esperar al CRUD de profesores/
alumnos del Módulo 14. `/auth/login` se queda público (obligatorio, es el propio login).
Tests: `tests/Feature/Auth/CreateUserAuthorizationTest.php` (401 sin token, 403 `AUTH02`
si el rol no es administrador).

---

## Módulo 5 — Dispositivos ESP32

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 5.1 | Migración `dispositivos` | ✅ | Ya existía desde la base del esquema como `devices` (`mac_address`, `ip`, `is_active`, `classroom_id`) |
| 5.2 | Modelo `Dispositivo` | ✅ | `App\Models\Device` |
| 5.3 | `DispositivoController` (CRUD admin) | ⏳ | Pertenece al rol Administrador (§8.4/Módulo 14), no construido aún |
| 5.4 | `DeviceAuthService` (token + MAC) | ❌ | **Descartado a propósito.** El usuario decidió, para este prototipo, que el endpoint que usa el ESP32 no lleve ninguna autenticación (ahorra tiempo/hardware). El device se identifica solo con su `mac_address` en el body. Ver 6.10 |
| 5.5 | Middleware `device.auth` | ❌ | Mismo motivo que 5.4 — no aplica, el endpoint del ESP32 es público a propósito |
| 5.6 | Endpoint `POST /device/heartbeat` | ⏳ | No pedido todavía, se agrega barato cuando haga falta |
| 5.7 | Comando `device:register {mac_address} {classroom_id}` | ✅ | Alta/actualización rápida de un device real sin esperar al CRUD de admin (5.3). Mismo espíritu que `governance:link-students` |

---

## Módulo 6 — Registro de asistencia NFC

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 6.1 | Migración `asistencias` | ✅ | Ya existía como `attendances` (índice compuesto vía `class_session_id` + `student_id` validado en servicio, no en BD) |
| 6.2 | Modelo `Asistencia` | ✅ | `App\Models\Attendance`. Enum estado: `PRESENTE`, `RETARDO`, `FALTA`, `JUSTIFICADA` |
| 6.3 | `AttendanceWindowService` | ✅ | Lógica de tolerancia dentro de `Device\NfcTapService::resolveStatus()` (y, por separado, `Profesor\RegisterNfcAttendanceService::resolveStatus()` para el flujo de app) |
| 6.4 | `AttendanceRegistrationService` | ✅ | `App\Services\Device\NfcTapService` |
| 6.5 | `DeviceAttendanceController` | ✅ | `App\Http\Controllers\Device\NfcController` |
| 6.6 | Form Request `NfcAttendanceRequest` | ✅ | `App\Http\Requests\Device\NfcTapRequest` |
| 6.7 | Evento `AttendanceRegistered` | ⏳ | No construido — no hay listeners que lo necesiten todavía (notificaciones = Módulo 10, sin construir) |
| 6.8 | Listener `WriteAttendanceAuditLog` | ⏳ | Depende de 6.7 y del Módulo 12 (Auditoría) |
| 6.9 | Tests de flujo NFC | ✅ | `tests/Feature/Device/NfcTapTest.php` — 11 tests: abre sesión, re-tap (`SES01`), `PRESENTE`/`RETARDO`, `USR02`, `ATT02`, `ATT01`, `SES02`, `SES04`, `DEV01`, `DEV04` |
| 6.10 | `POST /api/v1/device/nfc` — endpoint único, sin autenticación | ✅ | Un solo endpoint decide todo por el `nfc_uid`: si es el profesor del horario vigente en ese salón → abre la `class_session` (tolerancia cuenta desde `opened_at`, no desde `schedule.start_time`); si es un alumno → registra su asistencia contra la sesión ya abierta. Identificación del device por `mac_address` en el body, **sin token ni middleware** (decisión explícita del usuario para el prototipo — riesgo aceptado: cualquiera que conozca la MAC de un device real puede llamarlo). Código de error nuevo `SES04` (no está en el catálogo de `CLAUDE_CONTEXT.md`): "No hay una clase programada en este salón en este momento." No comparte código con `Profesor\RegisterNfcAttendanceService`/`OpenClassSessionService` (Módulo 7) — ese flujo de la app sigue intacto para cuando el profesor quiera pasar lista manualmente |
| 6.11 | Cierre automático de sesiones | ✅ | El usuario descartó cerrar por un segundo tap del profesor (se le podría olvidar y la clase quedaría abierta). En su lugar: comando `class-sessions:auto-close` programado cada 5 min (`routes/console.php`, `Schedule::command(...)->everyFiveMinutes()`) que cierra toda `class_session` `ABIERTA` cuyo `schedule.end_time` ya pasó, reutilizando `CloseClassSessionService::closeSession()` (extraído del `close()` existente de Módulo 7 — mismo comportamiento: marca `FALTA` a quien no registró, conteos). **No manda notificaciones a tutores todavía** (Módulo 10 no existe) — tampoco lo hacía el cierre manual existente. Test: `tests/Feature/Console/AutoCloseClassSessionsTest.php` |

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

> Cubierto funcionalmente por el Módulo 6 (NFC), con nombres distintos a los que
> ilustraba este tracker — no hace falta construir nada adicional para lo que este
> módulo describe.

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 9.1 | `AbsenceGenerationService` | ✅ | Es `CloseClassSessionService::closeSession()` (`app/Services/Profesor/CloseClassSessionService.php`) — marca `FALTA` a quien no registró al cerrar una sesión |
| 9.2 | Command `attendance:generate-absences` | ✅ | Es `class-sessions:auto-close` (Módulo 6.11), programado cada 5 min vía `Schedule::command()` en `routes/console.php` |
| 9.3 | Evento `AbsenceGenerated` | ❌ | No se construyó como evento de Laravel — `NotificationService::notifyAbsence()` se llama directo desde `closeSession()`, sin capa de eventos/listeners de por medio (menos indirección para lo que hace falta hoy) |
| 9.4 | Listener `NotifyTutorAboutAbsence` | ✅ | Es `NotificationService::notifyAbsence()` (Módulo 10), llamado directo sin listener |

---

## Módulo 10 — Tutores y notificaciones

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 10.1 | CRUD `tutores` | ⏳ | Pertenece al rol Administrador (§8.4, `POST /administrator/students` crea alumno+tutor juntos) — no construido. Por ahora los tutores solo se dan de alta por seeder (`TutorSeeder`) |
| 10.2 | Migración `alumno_tutor` (pivote) | ✅ | Ya existía como `student_tutor` (`relationship`, `is_primary`, `receives_notifications`). `database/seeders/StudentSeeder.php` ya vincula 1-2 tutores reales a cada alumno sembrado |
| 10.3 | Migración `notificaciones` | ✅ | Ya existía como `notifications` (`App\Models\AppNotification`). **Fix de esquema:** `user_id` era `NOT NULL`, pero los tutores familiares no tienen cuenta de usuario (`tutors` no tiene `users_id`) — imposible notificar a un tutor sin dejarlo en null. Se corrigió la migración (`user_id` ahora `nullable`), mismo criterio que otros gaps de esquema ya corregidos en este proyecto (`CareerFactory`, `DeviceFactory`) |
| 10.4 | Migración `preferencias_notificacion` | ✅ | Ya existía como `notification_preferences`. Se agregó `Tutor::booted()` (`app/Models/Tutor.php`) que crea la preferencia automáticamente (todo en `true`) al crear cualquier `Tutor` — cumple la regla de negocio documentada y cubre tanto el seeder como el futuro CRUD de admin sin tocar nada más |
| 10.5 | `NotificationService` | ✅ | `app/Services/NotificationService.php` — `notifyAbsence()`/`notifyLate()`. Filtra por `student_tutor.receives_notifications`, `tutors.is_active` y `notification_preferences.{absences,lates}`. Conectado en los tres puntos donde ya se crea una `Attendance`: `Profesor\CloseClassSessionService::closeSession()` (cubre cierre manual y `class-sessions:auto-close`, Módulo 6.11), `Device\NfcTapService::registerAttendance()` y `Profesor\RegisterNfcAttendanceService::register()` (ambos disparan `notifyLate()` en `RETARDO`). **Cubre `INASISTENCIA`/`RETARDO` automáticos + `AVISO`/etc. manuales** (ver `broadcast()`, Módulo 14.11). Canal de entrega real: WhatsApp vía Twilio (ver 10.6). Tests: `tests/Feature/Services/NotificationServiceTest.php` + assertions agregadas en `AutoCloseClassSessionsTest.php` y `NfcTapTest.php` |
| 10.6 | Canal de entrega por WhatsApp (Twilio) | ✅ | `App\Services\WhatsApp\TwilioWhatsAppService::sendMessage()` — llamada REST directa a la API de Twilio (`Http::asForm()->withBasicAuth(...)`, sin el SDK oficial, mismo criterio que `GovernanceClient`). Cableado en `NotificationService::createNotification()` (privado, compartido por `notify()` y `broadcast()`), así que **todo** disparador existente (ausencias/retardos automáticos, avisos manuales del admin) manda WhatsApp al tutor sin código adicional. `tutors.phone` se guarda sin lada (10 dígitos); se arma `+52` + dígitos por default (`TWILIO_WHATSAPP_DEFAULT_COUNTRY_CODE`, configurable). Si Twilio no está configurado (`.env` vacío) o la llamada falla, no pasa nada — no tumba la creación de la notificación (mismo criterio que el correo de bienvenida, Módulo 14.9c). **Nota de infraestructura:** se agregó `TWILIO_ACCOUNT_SID`/`TWILIO_AUTH_TOKEN` vacíos en `phpunit.xml` (mismo patrón que `MAIL_MAILER=array`) para que la suite nunca dependa de si el `.env` real ya tiene credenciales — sin esto, en cuanto el usuario configure Twilio de verdad, *todos* los tests que crean una notificación intentarían pegarle a la API real. **Nota operativa:** en el sandbox de Twilio cada tutor debe mandar "join <código>" por WhatsApp una vez antes de poder recibir mensajes — no es algo que el código controle. Tests: `tests/Feature/Services/TwilioWhatsAppServiceTest.php` (2) + 2 casos nuevos en `NotificationServiceTest.php` (manda WhatsApp cuando Twilio está configurado; la notificación se crea igual si Twilio falla) |

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

## Módulo 14 — Administrador Escolar (§8.4)

> Reemplaza la lista genérica anterior (`/api/v1/usuarios`, `/api/v1/docentes`, etc. no
> existían) por los endpoints reales de `role:administrador`. El rol en BD es
> **`administrador`** (español, `RoleSeeder`), no `administrator` como ilustra el `.md`
> — mismo tipo de discrepancia ya documentada para `director_carrera`/`career_director`.
> Rutas bajo `/api/v1/administrador/*`, namespace `App\Http\Controllers\Administrador`,
> middleware `['governance.auth', 'role:administrador']`.

### Tanda 1 — CRUDs de catálogo (✅ completa)

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.1 | CRUD carreras (`/administrador/careers`) | ✅ | `director_id` es **requerido** en el `POST` (el `.md` lo marca `opt`, pero `careers.director_id` es `NOT NULL` en el esquema real y `CareerFactory` ya lo trataba como obligatorio). Valida que el director tenga rol `director_carrera` (404 `USR03`). `DELETE` = soft delete (`is_active=false`), bloqueado por `CAR03` si tiene grupos activos |
| 14.2 | CRUD ciclos escolares (`/administrador/school-years`) | ✅ | Sin `DELETE` (no está en el `.md`, los ciclos no se eliminan). Al hacer `PUT` con `status=ACTIVO`, cualquier otro ciclo en `ACTIVO` pasa a `FINALIZADO` automáticamente. `status` por defecto `PROXIMO` (español, sigue la convención ya establecida) |
| 14.3 | CRUD materias (`/administrador/subjects`) | ✅ | Bloqueado por `SUBJ03` si tiene horarios activos |
| 14.4 | CRUD grupos (`/administrador/groups`) | ✅ | Bloqueado por `GRP04` si tiene alumnos activos (`User::scopeActive`) |
| 14.5 | CRUD dispositivos ESP32 (`/administrador/devices`) | ✅ | Incluye `GET /devices/{id}/ping` (`Http::timeout(5)->get("http://{ip}")`, 503 `DEV02` si no responde/timeout/sin IP). Complementa (no reemplaza) el comando `device:register` de la sesión pasada, que sigue sirviendo para altas rápidas sin este endpoint |
| 14.6 | Trait `RequiresDeletionConfirmation` | ✅ | `app/Http/Controllers/Concerns/RequiresDeletionConfirmation.php` — compartido entre los controladores que exigen `{ confirm: true }` en `DELETE` (422 `VAL03` si falta), evita repetirlo por controlador |
| 14.7 | Tests (`tests/Feature/Administrador/*`) | ✅ | 36 tests — `CareerTest`, `SchoolYearTest`, `SubjectTest`, `GroupTest`, `DeviceTest`. Nuevo helper `makeAdmin()` en `tests/Pest.php` |

### Tanda 2 — Alumnos + tutores (✅ completa)

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.9 | CRUD alumnos + tutores (`/administrador/students`, `.../students/{id}/tutors`) | ✅ | `POST /administrador/students` crea alumno+identidad en gobernanza+tutor+`student_tutor`+`notification_preferences` en un solo request (`App\Services\Administrador\CreateStudentService`) — completa de verdad el hueco del Módulo 10 (antes solo `TutorSeeder`/`StudentSeeder`). Sub-recurso de tutores con `POST/PUT/DELETE .../tutors[/{id}]` (`TUT01`/`TUT02`, reasignación de `is_primary`). El `.md` también menciona incluir `address` en el detalle del alumno — no se implementó, no hay CRUD de domicilios en este proyecto todavía |
| 14.9b | `ValidatesEvidenceFile` generalizado | ✅ | `app/Http/Requests/Concerns/ValidatesEvidenceFile.php` ahora acepta `maxSizeMb`/`allowedMimes` opcionales (antes fijo en 5MB/jpg-png-pdf) para reusarlo con la foto del alumno (3MB, solo jpg/png). Los 4 usos existentes (Alumno/Profesor) no cambiaron de comportamiento |
| 14.9c | Correo de bienvenida con contraseña temporal | ✅ | SMTP configurado (`.env` con Mailtrap; `.env.example` documenta Mailpit como alternativa local sin cuenta). `app/Mail/TemporaryPasswordMail.php` + `resources/views/emails/temporary-password.blade.php`, disparado desde `CreateStudentService::sendWelcomeEmail()` **sin bloquear la creación del alumno** si el envío falla (try/catch + log, ver `tests/Feature/Administrador/StudentTest.php` caso "still creates the student even if the welcome email fails to send"). La `temporary_password` se sigue regresando también en el JSON del `POST` (no solo por correo) — conveniente para pruebas y como respaldo si el correo no llega. Verificado con un envío real de prueba vía `php artisan tinker` contra Mailtrap |
| 14.9d | Tests (`StudentTest`, `StudentTutorTest`) | ✅ | 19 tests, incluye `Mail::fake()`/`Mail::assertSent()`. Nuevo helper `fakeGovernanceCreateUser()` en `tests/Pest.php` que fake-ea `POST {governance}/internal/users` sin tocar el fake de `/auth/me` (para poder usar ambos en el mismo test) |

**Nota de infraestructura:** durante esta sesión `bootstrap/cache/config.php` quedó cacheado con el `.env` real (`DB_CONNECTION=mysql`), lo que hizo que la suite completa de tests intentara usar MySQL en vez del `sqlite :memory:` que fuerza `phpunit.xml` (los `<env>` de phpunit no pueden ganarle a un config ya cacheado). Si `php artisan test` falla con errores de conexión MySQL/`Numeric value out of range` en `roles`, correr `php artisan config:clear` primero.

### Tanda 3 — Profesores (✅ completa)

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.8 | CRUD profesores (`/administrador/teachers`) | ✅ | Mismo patrón que 14.9 (alumnos): `App\Services\Administrador\CreateTeacherService` crea identidad en gobernanza + `users` (role_id `profesor` o `tutor_academico` si `is_academic_tutor` viene en el `POST`), sube foto (3MB, jpg/png), manda `TemporaryPasswordMail` y regresa `temporary_password` también en el JSON — mismo criterio ya usado en alumnos. `DELETE` bloqueado por `USR05` si el profesor tiene `schedules` activos |
| 14.8b | `PATCH /administrador/teachers/{id}/academic-tutor` | ✅ | El rol "tutor académico" no es un flag, es el rol `tutor_academico` (`RoleSeeder`) — el toggle cambia `users.role_id` entre `profesor`/`tutor_academico`, hace `upsert` de `academic_tutors.is_active` y, si vienen `group_ids`, sincroniza el pivote `group_academic_tutor`. `is_academic_tutor` se dejó fuera de `PUT /teachers/{id}` a propósito para no duplicar esta lógica (incluida la sincronización de grupos) en dos sitios |
| 14.8c | Trait `SendsWelcomePasswordEmail` | ✅ | `app/Services/Administrador/Concerns/SendsWelcomePasswordEmail.php` — se extrajo de `CreateStudentService` (Tanda 2) para reusar el mismo envío try/catch en `CreateTeacherService` sin duplicar código |
| 14.8d | Sin paginación real en `GET /administrator/teachers` | ✅ (decisión) | El `.md` menciona `page` como query opt, pero ningún listado de Administrador pagina hoy (mismo criterio ya aplicado en carreras/materias/grupos/dispositivos/alumnos) |
| 14.8e | Tests (`TeacherTest`, `TeacherAcademicTutorTest`) | ✅ | 15 tests. Nota de test: los roles (`profesor`, `tutor_academico`) solo se crean por `Role::firstOrCreate()` la primera vez que algo los necesita (via `UserFactory` o el propio servicio) — a diferencia de alumnos, donde `alumno` ya existía gratis por la definición base de `UserFactory`, aquí hubo que sembrar `profesor`/`tutor_academico` explícitamente en los tests que crean un profesor/tutor académico desde cero (sin pasar por una factory con ese estado primero). En producción esto no aplica: `RoleSeeder` siembra los 5 roles de una vez |

### Tanda 4 — Permisos de usuario (✅ completa)

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.10 | Permisos de usuario (`/administrador/users/permissions`, `/administrador/users/{id}/permissions[/override[/{oid}]]`) | ✅ | La infraestructura de datos ya existía (`permissions`, `permission_groups`, `user_permission_overrides`, modelos, `PermissionSeeder` sembrando un grupo `{rol}.full` por rol) — esta tanda solo construyó la capa HTTP: `App\Http\Controllers\Administrador\PermissionController` (`index` lista usuarios de cualquier rol con `search`/`role_id`/`has_overrides`; `show` regresa `role_permissions` + `overrides` + `effective_permissions`; `storeOverride`/`destroyOverride` gestionan `user_permission_overrides`). `type` es `PERMITIR`/`DENEGAR` (español, columna real), no `ALLOW`/`DENY` como dice el `.md` — mismo tipo de discrepancia ya documentada en otros módulos. `findUser()` **no filtra por rol** (a diferencia de Alumnos/Profesores) porque los permisos aplican a los 5 roles por igual |
| 14.10b | `User::rolePermissions()` | ✅ | Se extrajo de `User::effectivePermissions()` (ya existente) para poder mostrar los permisos del rol por separado de los overrides en el detalle. `effectivePermissions()` ahora reusa `rolePermissions()` en vez de repetir la query — mismo comportamiento, sin duplicar lógica |
| 14.10c | Fuera de alcance (decisión explícita) | — | El `.md` (§11.1/§11.2) también describe sub-roles del administrador como `permission_groups` individuales (`administrator.students`, `administrator.teachers`, etc.), un middleware `admin.module` que gatee cada ruta de Administrador por sub-rol, y un catálogo de permisos granulares por acción (`administrator.students.create`, `profesor.session.open`, etc.) que reemplazaría el catálogo simple de "ver página" que `PermissionSeeder` ya siembra. Implementarlo tocaría el middleware de *todas* las rutas ya construidas del proyecto — fuera de alcance de "permisos de usuario" tal como se pidió. Queda documentado aquí para si se retoma más adelante |
| 14.10d | Tests (`PermissionTest`) | ✅ | 10 tests. Como `Permission`/`PermissionGroup` no tienen factory, los tests arman la infraestructura mínima a mano (`grantRolePermission()` local, mismo patrón que `PermissionSeeder` en producción: crea el `Permission`, un `PermissionGroup`, y los encadena al rol) |

### Tanda 5 — Notificaciones admin (✅ completa)

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.11 | Notificaciones admin (`GET/POST /administrador/notifications`, `GET .../{id}`, `POST .../{id}/resend`) | ✅ | `App\Http\Controllers\Administrador\NotificationController`. Complementa `NotificationService` (Módulo 10), que antes solo creaba filas sin nada que las listara. `index` filtra por `type`/`is_read`/`date_from`/`date_to` (sin paginación real, mismo criterio del resto de Administrador); `store` resuelve destinatarios según `target` (`STUDENT/TUTOR/GROUP/CAREER/ALL`) y llama al nuevo `NotificationService::broadcast()`; `resend` reenvía a los mismos destinatarios originales o a un `target` nuevo si se manda en el body |
| 14.11b | Hallazgo de esquema: `notifications.student_id`/`tutor_id` son NOT NULL | ✅ (decisión) | La tabla modela siempre "aviso sobre el alumno X entregado al tutor familiar Y" — no existe un concepto de "notificación en lote". Un `POST` a un grupo/carrera/todos genera **una fila por cada par alumno-tutor afectado**, no una sola fila "broadcast". `target: STUDENT` y `target: TUTOR` se comportan igual (ambos piden `student_ids` y notifican a los tutores de esos alumnos) porque es el único canal de entrega que el esquema modela — no hay forma de notificar "directo" a la cuenta del alumno sin pasar por un tutor. La respuesta de `POST`/`resend` usa el `id` de la primera fila creada + `recipients_count` con el total, ya que el `.md` asume una sola entidad "notificación" |
| 14.11c | Migración `sent_by_user_id` en `notifications` | ✅ | Columna nueva, nullable, `restrictOnDelete()` — mismo patrón que `claims.action_by_user_id` (`2026_08_03_011201_add_action_tracking_to_claims_table.php`). El `.md` pide `sent_by` en el detalle pero el esquema original no rastreaba quién mandó el aviso; se agregó vía migración nueva (aprobado explícitamente por el usuario) en vez de tocar la migración original de `notifications`. Queda `null` en las notificaciones automáticas de Módulo 10 (`notifyAbsence`/`notifyLate`) |
| 14.11d | `NotificationService::broadcast()` | ✅ | Nuevo método público para avisos manuales no ligados a una `Attendance` (a diferencia de `notify()`, usado por `notifyAbsence()`/`notifyLate()`). Ambos ahora comparten un `createNotification()` privado — se eliminó la duplicación del `AppNotification::create(...)`. Mapa `TYPE_PREFERENCE_FIELDS` conecta cada `type` con su columna en `notification_preferences` (incluye `announcements`, que existía en el esquema desde Módulo 10 pero no se usaba todavía) |
| 14.11e | Tests (`NotificationTest`) | ✅ | 10 tests — listar con filtros, detalle con `sent_by` null, crear por `STUDENT`/`GROUP`/`CAREER` (fan-out y respeto de `announcements`), `422 NOT02` sin destinatarios válidos, `422 VAL01`, reenviar (mismos destinatarios y con `target` nuevo), `404 NOT01`. Se corrió también `NotificationServiceTest` (ya existente) para confirmar que el refactor de `createNotification()` no rompió `notifyAbsence()`/`notifyLate()` — sigue en verde |

Con esto, **todas las tandas acordadas del Módulo 14 (Administrador Escolar) quedan
completas**: catálogo, alumnos+tutores, profesores, permisos y notificaciones.

| # | Tarea | Estado | Notas |
|---|-------|--------|-------|
| 14.12 | Proteger `POST /api/v1/auth/users` | ✅ | Resuelto — ver Módulo 4.2. `role:administrador` |

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
9. **`POST /api/v1/device/nfc` sin autenticación** — El ESP32 del aula no maneja login ni
   tokens; se identifica solo con su `mac_address`. Decisión explícita del usuario para
   este prototipo (ahorrar tiempo/recursos), riesgo aceptado. Ver Módulo 6, fila 6.10.
10. **Tolerancia de asistencia por NFC cuenta desde `opened_at`, no desde
    `schedule.start_time`** — Solo aplica al flujo de `Device\NfcTapService`; el flujo
    de Profesor (Módulo 7, app) sigue usando `schedule.start_time` sin cambios.

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
