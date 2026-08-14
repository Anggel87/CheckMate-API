# CheckMate API — Referencia de integración para Frontend

> Generado a partir de lectura directa del código fuente (rutas, controladores, FormRequests, Resources, servicios y middlewares) de CheckMate-API (Laravel 12). Todos los paths mostrados son relativos a la raíz del servidor, ej. `https://tu-dominio/api/v1/...`.
>
> Este documento describe el **contrato real actual** del backend, incluyendo inconsistencias y limitaciones conocidas del código (se señalan explícitamente como "⚠️ Nota"). Si algo no se pudo verificar en este repositorio (p. ej. la forma exacta de la respuesta del servicio externo de Gobernanza), se indica explícitamente en vez de inventarse.

## Índice

0. [Cómo leer este documento](#0-cómo-leer-este-documento)
1. [Autenticación y autorización](#1-autenticación-y-autorización)
2. [Formato estándar de respuesta](#2-formato-estándar-de-respuesta)
3. [Subida de archivos (evidencias)](#3-subida-de-archivos-evidencias)
4. [Catálogo global de `error_code`](#4-catálogo-global-de-error_code)
5. [Auth (`/auth/*`)](#5-auth-authhome)
6. [Sesión (`/me`)](#6-sesión-me)
7. [Device — tap NFC físico (`/device/nfc`)](#7-device--tap-nfc-físico-devicenfc)
8. [Administrador (`/administrador/*`)](#8-administrador-administrador)
9. [Director de Carrera (`/director-carrera/*`)](#9-director-de-carrera-director-carrera)
10. [Profesor / Tutor Académico (`/profesor/*`)](#10-profesor--tutor-académico-profesor)
11. [Tutor Académico — reclamos y justificantes (`/tutor/*`)](#11-tutor-académico--reclamos-y-justificantes-tutor)
12. [Alumno (`/alumno/*`)](#12-alumno-alumno)
13. [Dev tools — solo entorno local (`/dev/*`)](#13-dev-tools--solo-entorno-local-dev)
14. [Notas transversales y gotchas importantes](#14-notas-transversales-y-gotchas-importantes)

---

## 0. Cómo leer este documento

- Todos los endpoints, salvo `POST /auth/login`, `POST /auth/users`, `POST /device/nfc` y los de `/dev/*`, cuelgan de un middleware de rol (`role:...`) que exige un usuario autenticado vía el mecanismo de Gobernanza (sección 1).
- **Todas** las respuestas usan el sobre estándar `{success, status_code, message, data, errors, meta}` (sección 2), **excepto** las excepciones explícitamente señaladas con ⚠️.
- Los campos de body se documentan con sus reglas de validación **exactas**, tal como están escritas en el FormRequest correspondiente (regex, `in:`, `max:`, etc.), copiadas literalmente del código.
- "Baja lógica" significa que el `DELETE` no borra la fila, solo marca `is_active`/`active = false`.

---

## 1. Autenticación y autorización

### 1.1 El login NO es local — delega en un servicio externo de "Gobernanza"

CheckMate-API no valida credenciales por sí mismo. `App\Services\Governance\GovernanceClient` llama a un microservicio externo:

```php
Http::baseUrl(config('services.governance.base_url'))  // env GOVERNANCE_BASE_URL
    ->withHeaders(['X-Client-Id' => ..., 'X-Client-Secret' => ...])
    ->acceptJson();
```

- `login(email, password, deviceName)` → `POST {GOVERNANCE_BASE_URL}/auth/login`
- `me(token)` → `GET {GOVERNANCE_BASE_URL}/auth/me` con `Authorization: Bearer {token}`
- `createUser(data)` → `POST {GOVERNANCE_BASE_URL}/internal/users` (credenciales de servidor-a-servidor `X-Client-Id`/`X-Client-Secret`, no el token del usuario)

**El Bearer token que el frontend debe enviar en cada request es el token que devuelve el login de Gobernanza**, no un token de Sanctum local. CheckMate-API valida ese token en cada request contra Gobernanza (con caché de 120s, ver 1.2).

### 1.2 Middleware `governance.auth` (`App\Http\Middleware\ResolveGovernanceUser`)

Aplicado a prácticamente todas las rutas protegidas. Flujo:

1. Lee `Authorization: Bearer <token>`. Si falta → **401 `AUTH05`** "Tu sesión ha expirado. Inicia sesión nuevamente."
2. Resuelve `governance_user_id` llamando a `GovernanceClient::me($token)`, **cacheado 120s** (`GOVERNANCE_AUTH_CACHE_TTL`, key `governance:auth:sha256(token)`). Si la llamada falla o no retorna `data.user.id` → mismo **401 `AUTH05`** (y ese resultado nulo también queda cacheado 120s, así que un fallo transitorio del servicio de Gobernanza puede "atascar" el 401 durante ese tiempo).
3. Busca `User::where('governance_user_id', ...)` en la BD local. Si no hay perfil local vinculado → **403 sin `error_code`** ("Tu cuenta no está vinculada a un perfil local todavía.").
4. Si el usuario local existe pero `active = false` → **403 `AUTH03`** ("Tu cuenta está desactivada. Contacta al administrador.").
5. Si todo pasa, `Auth::setUser($user)` — de ahí en adelante `request()->user()` es el modelo `User` local.

### 1.3 Middleware `role:<rol1>,<rol2>,...`

Compara `$user->role->name` contra la lista de roles de la ruta. Si no coincide → **403 `AUTH02`** ("No tienes permiso para acceder a este portal."). Siempre va **después** de `governance.auth` en la cadena de middlewares.

### 1.4 Roles del sistema

Sembrados en `RoleSeeder` (tabla `roles`, FK `users.role_id`):

```
alumno | profesor | tutor_academico | administrador | director_carrera
```

⚠️ **Discrepancia importante**: el body de `POST /auth/users` (creación de usuario) valida el campo `role` contra un **vocabulario distinto**: `in:profesor,tutor_academico,alumno,administrator,career_director` — nota `administrator` y `career_director` en inglés, en vez de `administrador`/`director_carrera`. Esto ocurre porque ese payload se reenvía tal cual al servicio externo de Gobernanza, que usa su propio vocabulario de roles. **No confundir uno con otro**: el rol usado por `role:` en las rutas locales siempre es el español (`administrador`, `director_carrera`).

⚠️ **El módulo "Tutor" es engañoso por nombre.** Hay dos entidades distintas:
- `App\Models\Tutor` (tabla `tutors`): tutor **familiar/legal** de un alumno (padre/madre/encargado). **No tiene login ni rutas API propias.**
- `App\Models\AcademicTutor` (tabla `academic_tutors`): un **profesor** designado tutor de uno o varios grupos. Es un `User` normal con rol `tutor_academico`, y **es quien usa `/api/v1/tutor/*`**.

Además, `role:profesor,tutor_academico` protege **todo el módulo Profesor** (`/profesor/*`): cualquier usuario con rol `tutor_academico` puede usar también todas las rutas de `/profesor/*`, no solo `/tutor/*`.

Y dentro de `claims`, la columna `claims.tutor_id` (FK a `users`) **no es un tutor familiar**: guarda el id del **alumno que presentó el reclamo** (nombre de columna heredado/confuso, confirmado en comentarios del propio código).

### 1.5 `GET /api/v1/me` — resolver la sesión actual

Primer endpoint a llamar tras el login para saber quién es el usuario y qué puede hacer.

- **Auth:** `governance.auth` (cualquier rol).
- **200 OK:**
```json
{
  "success": true, "status_code": 200,
  "message": "Sesion resuelta correctamente.",
  "data": {
    "id": 1,
    "full_name": "Ana Pérez López",
    "email": "ana@ejemplo.com",
    "role": "alumno",
    "permissions": ["attendance.view", "..."]
  },
  "errors": null, "meta": { "request_id": "...", "api_version": "v1", "timestamp": "..." }
}
```
- `permissions`: lista de `key_name` strings — la unión de los permisos del rol (vía `permission_groups`) más los overrides individuales `PERMITIR` menos los `DENEGAR` (ver `Administrador\PermissionController` en la sección 8.9 para cómo se administran).
- **Errores:** `401 AUTH05`, `403` sin código (no vinculado), `403 AUTH03` (cuenta desactivada) — todos del middleware `governance.auth`.

---

## 2. Formato estándar de respuesta

Definido en `App\Traits\ApiResponse` y `App\Exceptions\ApiException`. **Excepción global**: `POST /auth/login` y `POST /auth/users` devuelven el JSON crudo del servicio de Gobernanza, sin este sobre (ver sección 5).

### 2.1 Éxito simple

```json
{
  "success": true,
  "status_code": 200,
  "message": "Mensaje descriptivo en español.",
  "data": { "...": "..." },
  "errors": null,
  "meta": {
    "request_id": "req_xxxxx o el valor del header X-Request-ID si lo mandaste",
    "api_version": "v1",
    "timestamp": "2026-08-10T12:00:00+00:00"
  }
}
```

### 2.2 Éxito paginado

Igual que arriba, pero `meta` agrega `pagination`. Todos los listados paginados del backend usan **`paginate(20)` fijo** (no hay parámetro para cambiar `per_page`); se navega con `?page=N`.

```json
"meta": {
  "request_id": "...", "api_version": "v1", "timestamp": "...",
  "pagination": {
    "current_page": 1, "per_page": 20, "total": 57,
    "last_page": 3, "from": 1, "to": 20, "has_more_pages": true
  }
}
```

### 2.3 Error (`ApiException`)

```json
{
  "success": false,
  "status_code": 404,
  "message": "Mensaje descriptivo en español.",
  "data": null,
  "errors": null,
  "error_code": "GRP02",
  "meta": { "request_id": "...", "api_version": "v1", "timestamp": "..." }
}
```

⚠️ **La clave `error_code` solo aparece si el backend la pasó explícitamente.** No asumas que siempre existe — a veces está ausente por completo (no es `null`, simplemente no está en el JSON).

Mapeo de HTTP status por helper:

| Helper | HTTP |
|---|---|
| `notFound()` | 404 |
| `forbidden()` | 403 |
| `conflict()` | 409 |
| `unauthorized()` | 401 |
| `unprocessable()` | 422 |
| `payloadTooLarge()` | 413 |
| `unsupportedMediaType()` | 415 |

### 2.4 Error de validación de FormRequest

Manejado **globalmente** (`bootstrap/app.php`), siempre:

```json
{
  "success": false,
  "status_code": 422,
  "message": "Datos inválidos. Revisa los campos marcados.",
  "data": null,
  "errors": { "email": ["El correo es obligatorio."], "phone": ["..."] },
  "error_code": "VAL01",
  "meta": { ... }
}
```

`VAL01` también se reutiliza manualmente en algunos controladores para errores de negocio simples que no vienen de un FormRequest (ej. día de la semana inválido en `GET /profesor/schedule`, o la comparación de tolerancias en `PUT /administrador/attendance-settings/{id}`).

### 2.5 ⚠️ Inconsistencias de formato a vigilar

1. **`POST /auth/login` y `POST /auth/users`** devuelven el JSON **crudo** del servicio de Gobernanza — sin el sobre `{success,...}`. Su forma exacta no vive en este repositorio.
2. **`GET /api/user`** (sin `v1/`, con middleware `auth:sanctum`, no `governance.auth`) es scaffolding por defecto de Laravel, probablemente código muerto — no lo uses.
3. Algunos endpoints usan **route-model-binding implícito** de Eloquent (`Claim $claim`, `Justification $justification`, `Subject $subject` en el módulo Alumno). Si el ID no existe, Laravel lanza un 404 **con el formato por defecto de Laravel** (`{"message": "No query results for model [...] {id}"}`), **no** el sobre estándar ni `error_code`. Solo cuando el recurso existe pero no pertenece al usuario se obtiene el 403 `PERM01` con el sobre estándar. Afecta: `GET /alumno/claims/{claim}`, `GET /alumno/justifications/{justification}`, `GET /alumno/subjects/{subject}`, `GET /alumno/subjects/{subject}/attendance`.
4. El resto de rutas con parámetros de ruta (`{group}`, `{student}`, `{incident}`, etc.) usan un `int` simple con búsqueda manual (`Model::find($id)`), y sí devuelven `404` con `error_code` propio y el sobre estándar si no existe.

---

## 3. Subida de archivos (evidencias)

Trait `App\Http\Requests\Concerns\ValidatesEvidenceFile` (o su equivalente en Administrador para fotos), invocado **después** de que el FormRequest ya validó `file`/`nullable`/`required`. Es una segunda capa manual — por eso sus errores son `ApiException` con HTTP 413/415, **no** un 422 de validación.

- Tamaño máximo por defecto: **5 MB** (evidencias de incidentes/reclamos/justificantes) o **3 MB** (fotos de alumno/profesor en Administrador). Si excede → **413 `FILE01`** "El archivo supera el límite de X MB."
- Tipos MIME permitidos (detectados por contenido real del archivo, no por extensión):
  - Evidencias (incidentes, reclamos, justificantes): `image/jpeg`, `image/png`, `application/pdf`.
  - Fotos (Administrador): `image/jpeg`, `image/png`.
  - Si el mime no aplica → **415 `FILE02`** "Tipo de archivo no permitido."
- Si el campo es `nullable` y no se envía archivo, no se valida nada más (evidencia efectivamente opcional). Si es `required` (justificantes de alumno), el propio FormRequest ya exige el archivo antes de llegar a esta capa.
- Los archivos se guardan en el disco `public`, en carpetas por tipo: `incidents/`, `claims/`, `justifications/`, `users/`. Las URLs en las respuestas usan `Storage::url($path)`.

---

## 4. Catálogo global de `error_code`

Tabla consolidada de **todos** los códigos de error del sistema (útil para mapear un `error_code` a un mensaje/UI sin tener que ir módulo por módulo).

### Autenticación / autorización (transversal a toda la API)

| Código | HTTP | Significado |
|---|---|---|
| `AUTH05` | 401 | Token ausente o Gobernanza no resolvió el usuario (sesión expirada) |
| *(sin código)* | 403 | Usuario válido en Gobernanza pero sin perfil local vinculado |
| `AUTH03` | 403 | Cuenta local desactivada |
| `AUTH02` | 403 | Rol del usuario no autorizado para este portal |
| `VAL01` | 422 | Validación de FormRequest fallida, o regla de negocio simple validada manualmente |
| `VAL02` | 422 | Rango de fechas inválido (`date_to < date_from`) |
| `VAL03` | 422 | Falta `confirm=true` en una baja lógica que lo requiere |
| `PERM01` | 403 | Acceso a un recurso fuera del alcance del usuario (grupo/carrera/sesión/alumno ajeno) |
| `PERM02` | 403 | Alumno intenta reclamar una materia en la que no está inscrito |

### Careers / Grupos / Ciclos / Materias / Aulas (Administrador, Director)

| Código | HTTP | Significado |
|---|---|---|
| `CAR01` | 404 | Carrera no existe |
| `CAR02` | 409 | Código de carrera duplicado |
| `CAR03` | 409 | No se puede dar de baja una carrera con grupos activos |
| `GRP01` | 404 | Año escolar no existe / no hay uno activo (contexto Profesor) |
| `GRP02` | 404 | Grupo no existe |
| `GRP03` | 409 | Grupo duplicado (mismo grado+sección+carrera+ciclo) |
| `GRP04` | 409 | No se puede dar de baja un grupo con alumnos activos |
| `SY01` | 404 | Ciclo escolar no existe |
| `SY02` | 409 | Nombre de ciclo escolar duplicado |
| `SUBJ01` | 404 | Materia no existe |
| `SUBJ02` | 409 | Código de materia duplicado |
| `SUBJ03` | 409 | No se puede dar de baja una materia con horarios activos |
| `CLS01` | 404 | Salón (classroom) no existe |
| `SCH01` | 404 | Horario (schedule) no existe |

### Usuarios / Personal (Administrador, Director, Profesor)

| Código | HTTP | Significado |
|---|---|---|
| `USR01` | 404 | Usuario (alumno/profesor) no existe o no tiene el rol esperado |
| `USR02` | 404 | Tarjeta NFC no reconocida (`nfc_uid` no coincide con ningún usuario) |
| `USR03` | 404 | Usuario indicado como director no existe o no tiene el rol correcto |
| `USR04` | 409 | Email duplicado al crear usuario |
| `USR05` | 409 | No se puede dar de baja a un profesor con horarios activos |
| `TUT01` | 404 | Tutor familiar no existe o no está asignado a ese alumno |
| `TUT02` | 409 | No se puede eliminar al único tutor familiar de un alumno |

### Dispositivos (Administrador, Director, Device)

| Código | HTTP | Significado |
|---|---|---|
| `DEV01` | 404 | Dispositivo no existe |
| `DEV02` | 503 | Dispositivo no respondió al ping |
| `DEV03` | 409 | MAC address duplicada |
| `DEV04` | 403 / 409 | Dispositivo ya dado de baja (403 en `/device/nfc`, 409 en `destroy` de Administrador) |

### Sesiones de clase y asistencia (Device, Profesor, Dev)

| Código | HTTP | Significado |
|---|---|---|
| `SES01` | 409 | Ya existe una sesión abierta/registrada para ese horario+fecha |
| `SES02` | 404 | La sesión no existe o ya fue cerrada |
| `SES03` | 409 | La sesión ya fue cerrada anteriormente |
| `SES04` | 404 | No hay clase programada en ese salón en este momento |
| `ATT01` | 409 | El alumno ya registró asistencia en esa sesión |
| `ATT02` | 403 | El alumno no pertenece al grupo de esa clase |
| `ATT03` | 409 **o** 404 | Sin asistencias en la materia (409, al crear un claim) **/** la asistencia indicada no existe o no es de esa materia (404, al justificar) — ⚠️ mismo código, dos HTTP distintos según endpoint |
| `ATT04` | 409 | La asistencia no está en estado `FALTA` (no se puede justificar) |

### Incidentes (Director, Profesor)

| Código | HTTP | Significado |
|---|---|---|
| `INC01` | 404 | Incidente no existe (o invisible por estar fuera de la carrera del director) |
| `INC02` | 409 | Incidente ya cerrado, no se puede editar (usado por Profesor) |
| `INC03` | 409 | Incidente ya cerrado/cancelado, no se puede editar/cerrar (usado por Director) |

### Reclamos y justificantes (Director, Profesor, Tutor Académico, Alumno)

| Código | HTTP | Significado |
|---|---|---|
| `CLM01` | 404 | Reclamo no existe o fuera de alcance |
| `CLM02` | 409 | Reclamo ya resuelto (`ACEPTADO`/`RECHAZADO`) — estado terminal |
| `JUST01` | 404 | Justificante no existe o no pertenece al alumno indicado |
| `JUST02` | 409 | Justificante ya revisado (no está `PENDIENTE`) |
| `JUST03` | 409 | Ya existe un justificante para esa asistencia |

### Permisos (Administrador)

| Código | HTTP | Significado |
|---|---|---|
| `PERM03` | 404 | Permiso indicado no existe |
| `PERM04` | 409 | Ya existe una regla de override para ese usuario+permiso |
| `PERM05` | 404 | Regla de override no existe / no pertenece a ese usuario |

### Configuración de asistencia (Administrador)

| Código | HTTP | Significado |
|---|---|---|
| `ATS01` | 404 | Configuración de tolerancia no existe |
| `ATS02` | 409 | Ese horario ya tiene una configuración de tolerancia |

### Notificaciones (Administrador)

| Código | HTTP | Significado |
|---|---|---|
| `NOT01` | 404 | Notificación no existe |
| `NOT02` | 422 | No se resolvió ningún destinatario válido |

### Auditoría (Director)

| Código | HTTP | Significado |
|---|---|---|
| `LOG01` | 404 | Registro de auditoría no existe o fuera de alcance |

### Archivos

| Código | HTTP | Significado |
|---|---|---|
| `FILE01` | 413 | Archivo supera el límite de tamaño |
| `FILE02` | 415 | Tipo de archivo no permitido |

### Sin `error_code` (mensaje genérico, HTTP variable)

- 403 "Tu cuenta no está vinculada a un perfil local todavía." (`governance.auth`)
- 503 "No se pudo conectar con gobernanza. Inténtalo más tarde." (creación de alumno/profesor)
- 404 "El salón de este horario no tiene ningún dispositivo activo registrado..." (`/dev/schedules/{id}/activate-now`)
- 404 "La sesión de clase solicitada no existe." / 409 "Esta sesión ya fue cerrada anteriormente." (`/dev/class-sessions/{id}/close-now`)

---

## 5. Auth (`/auth/*`)

⚠️ **Estos dos endpoints NO usan el sobre estándar de respuesta** — devuelven el JSON crudo del servicio externo de Gobernanza. Su forma exacta debe confirmarse contra ese servicio (fuera de este repositorio).

### `POST /auth/login`

- **Auth:** ninguna (pública).
- **Body** (validado inline, sin FormRequest):

| Campo | Requerido | Reglas |
|---|---|---|
| `email` | sí | `required, email` |
| `password` | sí | `required, string` |
| `device_name` | no | `sometimes, string` — default `"checkmate-api"` |

- **Respuesta:** JSON crudo de Gobernanza (probablemente incluye un token, forma exacta no verificable desde este repo). HTTP 200 (default de `response()->json()`).
- **Errores:** `422 VAL01` si falla la validación local. Credenciales inválidas / errores de Gobernanza **no están mapeados** a un `error_code` propio — se propagan como excepción HTTP genérica (posible 500 si `APP_DEBUG` está activo mostrará traza).

### `POST /auth/users` — crear usuario (solo Administrador)

- **Auth:** `governance.auth` + `role:administrador`.
- **Body:**

| Campo | Requerido | Reglas |
|---|---|---|
| `name` | sí | `required, string, max:255` |
| `email` | sí | `required, email, max:255` |
| `role` | sí | `required, string, in:profesor,tutor_academico,alumno,administrator,career_director` (⚠️ ver discrepancia de vocabulario en 1.4) |
| `active` | no | `sometimes, boolean` |
| `password` | no | `sometimes, string` (sin reglas de complejidad) |

- **Respuesta:** JSON crudo de Gobernanza, HTTP 200.
- **Errores:** `401 AUTH05`, `403` sin código, `403 AUTH03`, `403 AUTH02` (middlewares), `422 VAL01` (validación local). Errores de Gobernanza (email duplicado, etc.) no mapeados.

---

## 6. Sesión (`/me`)

Ver sección [1.5](#15-get-apiv1me--resolver-la-sesión-actual).

---

## 7. Device — tap NFC físico (`/device/nfc`)

Endpoint **público** (sin `governance.auth` ni `role:`), llamado directamente por el dispositivo Raspberry Pi/NFC del salón. La "autenticación" es que la `mac_address` corresponda a un `Device` dado de alta y activo.

### `POST /device/nfc`

- **Auth:** ninguna.
- **Body** (`Device\NfcTapRequest`):

| Campo | Requerido | Reglas |
|---|---|---|
| `mac_address` | sí | `required, string, regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/` (formato `AA:BB:CC:DD:EE:FF`) |
| `nfc_uid` | sí | `required, string, regex:/^[A-Fa-f0-9:\- ]{1,100}$/` |
| `scanned_at` | no | `sometimes, date_format:Y-m-d\TH:i:s` — sirve para **simular tiempo** en pruebas/demo; si se omite se usa la hora del servidor |

**Lógica de negocio (`App\Services\Device\NfcTapService`):**

1. Busca `Device` por `mac_address` → si no existe: **404 `DEV01`**. Si `is_active = false`: **403 `DEV04`**.
2. Busca un `Schedule` activo del salón del dispositivo vigente en este momento (día de la semana + rango horario) → si no hay: **404 `SES04`**.
3. Busca el usuario dueño del `nfc_uid` → si no coincide con nadie: **404 `USR02`**.
4. **Decisión profesor vs. alumno (no es un campo del body, se infiere):** si el UID es del profesor titular del horario → se **abre la sesión**. En cualquier otro caso, se trata como alumno y se **registra asistencia**.

**Caso profesor → abrir sesión:**
- Si ya hay sesión `ABIERTA` para ese horario/fecha: **409 `SES01`**. Si ya está `CERRADA`: **409 `SES03`**.
- Si no, crea `ClassSession` (`status: ABIERTA`, `opening_method: NFC`, `opened_at: now`).
- **201:**
```json
{ "data": { "event": "session_opened", "session_id": 100, "opened_at": "2026-08-10T08:00:00+00:00" } }
```

**Caso alumno → registrar asistencia:**
- Si el alumno no pertenece al grupo del horario: **403 `ATT02`**.
- Si no hay sesión `ABIERTA` para ese horario hoy (el profesor no ha abierto su sesión primero): **404 `SES02`**.
- Si el alumno ya registró asistencia en esa sesión: **409 `ATT01`**.
- **Cálculo de tolerancia**: compara `scanned_at` (o `now()`) contra `session.opened_at`. Tolerancia = `schedule.settings.present_tolerance_minutes` (default **10 min** si no hay `AttendanceSetting`). Si la diferencia ≤ tolerancia → `PRESENTE`; si no → `RETARDO`. (`FALTA` **nunca** se calcula aquí — solo al cerrar la sesión, ver sección 10).
- **201:**
```json
{ "data": { "event": "attendance_registered", "student_id": 10, "full_name": "Ana Pérez López", "status": "PRESENTE", "checked_in_at": "2026-08-10T08:02:00+00:00" } }
```
- Si `status === "RETARDO"`, se notifica automáticamente a los tutores familiares (WhatsApp, best-effort, no bloquea la respuesta).

**Errores de este endpoint:**

| HTTP | `error_code` | Causa |
|---|---|---|
| 422 | `VAL01` | Body inválido |
| 404 | `DEV01` | Dispositivo no existe |
| 403 | `DEV04` | Dispositivo dado de baja |
| 404 | `SES04` | No hay clase vigente en este salón ahora |
| 404 | `USR02` | UID no reconocido |
| 409 | `SES01` | Sesión ya abierta (toque del profesor) |
| 409 | `SES03` | Sesión ya cerrada (toque del profesor) |
| 403 | `ATT02` | Alumno no pertenece al grupo |
| 404 | `SES02` | Sesión no abierta (el profesor no ha tocado su tarjeta) |
| 409 | `ATT01` | Asistencia duplicada |

---

## 8. Administrador (`/administrador/*`)

**Auth de todo el módulo:** `governance.auth` + `role:administrador`.

Convenciones del módulo:
- **Toda "eliminación" es baja lógica** (`is_active`/`active = false`), excepto `destroyOverride` de permisos (delete físico).
- **`confirm=true` obligatorio** (query o body, `422 VAL03` si falta) en: `DELETE careers/{id}`, `groups/{id}`, `subjects/{id}`, `students/{id}`, `teachers/{id}`. **No** obligatorio en `devices/{id}`, `attendance-settings/{id}`, `permissions/override/{id}`.
- La mayoría de las unicidades (`code`, `email`, `mac_address`, nombre de ciclo) se validan **manualmente** en el controlador/servicio, devolviendo **409** con `error_code` propio — no son reglas `unique:` de Laravel ni 422.
- Auditoría (`audit_logs`) activa solo en: **Device, Group, Student, Teacher** (Career, SchoolYear, Subject, AttendanceSetting, Notification, Permission **no** auditan).
- Ningún endpoint de este módulo pagina (`->get()` siempre, no `paginate()`).

### 8.1 Careers

`Route::apiResource('careers', CareerController::class)`.

| Método | Path | Notas |
|---|---|---|
| GET | `/careers` | Query: `include_inactive` (bool) |
| GET | `/careers/{career}` | Carga `director`, `groups_count` |
| POST | `/careers` | ver body abajo |
| PUT/PATCH | `/careers/{career}` | mismos campos, todos `sometimes` |
| DELETE | `/careers/{career}` | baja lógica, requiere `confirm=true` |

**Body `POST /careers`:**

| Campo | Reglas |
|---|---|
| `name` | `required, string, regex:/^.{3,150}$/` |
| `short_name` | `sometimes, nullable, string, max:20` |
| `code` | `required, string, regex:/^[A-Z0-9\-]{2,30}$/` |
| `director_id` | `required, integer` (debe existir y tener rol `director_carrera` → 404 `USR03`) |

Respuesta `AdminCareerResource`: `id, name, short_name, code, is_active, director{id,full_name}, groups_count`.

**Errores:** `CAR01` (404), `CAR02` (409, código duplicado), `CAR03` (409, baja con grupos activos), `USR03` (404), `VAL03` (422).

### 8.2 Devices

`Route::apiResource('devices', DeviceController::class)` + `GET devices/{device}/ping`.

| Método | Path | Notas |
|---|---|---|
| GET | `/devices` | Query: `classroom_id`, `is_active` |
| GET | `/devices/{device}` | |
| GET | `/devices/{device}/ping` | Hace `Http::timeout(5)->get("http://{ip}")` |
| POST | `/devices` | ver body |
| PUT/PATCH | `/devices/{device}` | `ip`, `classroom_id`, `is_active` (⚠️ `mac_address` NO editable) |
| DELETE | `/devices/{device}` | baja lógica, **sin** `confirm=true` |

**Body `POST /devices`:**

| Campo | Reglas |
|---|---|
| `mac_address` | `required, string, regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/` — se normaliza a MAYÚSCULAS al guardar |
| `ip` | `sometimes, nullable, ip` |
| `classroom_id` | `required, integer` |

Respuesta `AdminDeviceResource`: `id, mac_address, ip, is_active, classroom_id`. Ping exitoso: `{ "status": "ONLINE" }`.

**Errores:** `DEV01` (404), `DEV02` (503, ping falla), `DEV03` (409, MAC duplicada), `DEV04` (409, ya dado de baja), `CLS01` (404, salón no existe).

### 8.3 Groups

`Route::apiResource('groups', GroupController::class)`.

| Método | Path | Notas |
|---|---|---|
| GET | `/groups` | Query: `career_id`, `school_year_id`, `shift`, `is_active` |
| GET | `/groups/{group}` | Carga `academic_tutors` |
| POST | `/groups` | ver body |
| PUT/PATCH | `/groups/{group}` | mismos campos + `is_active` |
| DELETE | `/groups/{group}` | baja lógica, `confirm=true` |

**Body `POST /groups`:**

| Campo | Reglas |
|---|---|
| `school_year_id` | `required, integer` (404 `SY01` si no existe) |
| `career_id` | `required, integer` (404 `CAR01` si no existe) |
| `grade` | `required, string, regex:/^[A-Za-z0-9]{1,5}$/` |
| `section` | `required, string, regex:/^[A-Za-z0-9]{1,5}$/` |
| `shift` | `sometimes, nullable, string, in:MATUTINO,VESPERTINO,INGENIERIA` |

⚠️ Unicidad compuesta `(school_year_id, career_id, grade, section)` es un constraint físico de BD, capturado vía `try/catch(QueryException)` → **409 `GRP03`** (no hay validación previa manual).

Respuesta `AdminGroupResource`: `id, school_year_id, career_id, grade, section, shift, is_active, academic_tutors[]`.

**Errores:** `SY01` (404), `CAR01` (404), `GRP02` (404), `GRP03` (409), `GRP04` (409, baja con alumnos activos).

### 8.4 School Years

Rutas manuales (**no** `apiResource`, **no existe `destroy`**, update solo acepta `PUT`, no `PATCH`):

```
GET  /school-years
GET  /school-years/{schoolYear}
POST /school-years
PUT  /school-years/{schoolYear}
```

**Body `POST /school-years`:**

| Campo | Reglas |
|---|---|
| `name` | `required, string, regex:/^\d{4}-\d{4}$/` (ej. `2026-2027`) |
| `start_date` | `required, date` |
| `end_date` | `required, date, after:start_date` |

`status` se fuerza a `PROXIMO` en creación (no es input).

**Body `PUT /school-years/{id}`:** mismos campos `sometimes` + `status` (`sometimes, string, in:PROXIMO,ACTIVO,FINALIZADO`).

⚠️ **Regla no obvia:** si envías `status: "ACTIVO"`, el backend **automáticamente pasa a `FINALIZADO`** cualquier otro ciclo escolar que estuviera `ACTIVO` — solo puede haber un ciclo activo a la vez.

Respuesta `AdminSchoolYearResource`: `id, name, start_date, end_date, status, groups_count`.

**Errores:** `SY01` (404), `SY02` (409, nombre duplicado), `VAL01` (422).

### 8.5 Subjects

`Route::apiResource('subjects', SubjectController::class)`.

| Método | Path | Notas |
|---|---|---|
| GET | `/subjects` | Query: `search`, `is_active` |
| GET | `/subjects/{subject}` | |
| POST | `/subjects` | ver body |
| PUT/PATCH | `/subjects/{subject}` | mismos campos `sometimes` + `is_active` |
| DELETE | `/subjects/{subject}` | baja lógica, `confirm=true` |

**Body `POST /subjects`:**

| Campo | Reglas |
|---|---|
| `name` | `required, string, regex:/^.{3,100}$/` |
| `code` | `required, string, regex:/^[A-Z0-9\-]{2,30}$/` |
| `description` | `sometimes, nullable, string, max:255` |

Respuesta `AdminSubjectResource`: `id, name, code, description, is_active, schedules_count`.

**Errores:** `SUBJ01` (404), `SUBJ02` (409, código duplicado), `SUBJ03` (409, baja con horarios activos).

### 8.6 Students

`Route::apiResource('students', StudentController::class)` + sub-rutas de tutores familiares. `store`/`update` son `multipart/form-data` (por `photo`).

| Método | Path | Notas |
|---|---|---|
| GET | `/students` | Query: `search`, `group_id`, `career_id`, `active` |
| GET | `/students/{student}` | Carga `tutors` |
| POST | `/students` | crea alumno **y** su primer tutor familiar |
| PUT/PATCH | `/students/{student}` | sin campos `tutor_*` |
| DELETE | `/students/{student}` | baja lógica, `confirm=true` |
| POST | `/students/{student}/tutors` | agregar tutor familiar |
| PUT | `/students/{student}/tutors/{tutor}` | editar tutor familiar |
| DELETE | `/students/{student}/tutors/{tutor}` | quitar tutor familiar |

**Body `POST /students`:**

| Campo | Reglas |
|---|---|
| `first_name` | `required, string, regex:/^[A-Za-zÀ-ÿ\s]{2,45}$/` |
| `second_name` | `sometimes, nullable, string, max:45` |
| `first_surname` | `required, string, max:45` |
| `second_surname` | `required, string, max:45` |
| `email` | `required, email, max:155` |
| `phone` | `required, string, regex:/^\d{10}$/` |
| `birth_date` | `required, date` |
| `gender` | `required, string, in:M,F,OTRO` |
| `group_id` | `required, integer` (404 `GRP02` si no existe) |
| `photo` | `sometimes, nullable, file` (máx 3MB, jpeg/png) |
| `tutor_first_name` | `required, string, max:45` |
| `tutor_first_surname` | `required, string, max:45` |
| `tutor_second_surname` | `sometimes, nullable, string, max:45` |
| `tutor_phone` | `required, string, regex:/^\d{10}$/` |
| `tutor_relationship` | `required, string, max:50` |

Flujo: valida `group_id` y `email` único (409 `USR04`) → crea usuario vía Gobernanza (rol `alumno`) → crea `User` local con contraseña aleatoria (el login real pasa por Gobernanza) → crea el `Tutor` familiar y lo vincula (`is_primary: true`, `receives_notifications: true`) → si Gobernanza retorna `temporary_password`, se adjunta en la respuesta **solo de `store`** y se intenta enviar por correo (no bloqueante).

Respuesta `AdminStudentResource`: `id, first_name, second_name, first_surname, second_surname, email, phone, birth_date, gender, active, photo_url, group_id, tutors[]` (`{id, full_name, phone, relationship, is_primary, receives_notifications}`), `temporary_password` (solo en `store`).

**Sub-rutas de tutores familiares:**

`POST /students/{student}/tutors` — body: `first_name*, second_name, first_surname*, second_surname*, phone* (regex 10 dígitos), relationship*, is_primary (bool), receives_notifications (bool)`. Si `is_primary: true`, desmarca a los demás tutores del alumno como no-primarios primero. Crea un `Tutor` **nuevo** siempre (no reutiliza por coincidencia de datos).

`PUT /students/{student}/tutors/{tutor}` — mismos campos `sometimes`. 404 `TUT01` si el tutor no pertenece a ese alumno.

`DELETE /students/{student}/tutors/{tutor}` — 409 `TUT02` si es el único tutor del alumno (no se permite dejarlo sin ninguno). Solo desvincula (`detach`), no borra el registro `Tutor`.

**Errores:** `USR01` (404), `GRP02` (404), `USR04` (409, email duplicado), `TUT01` (404), `TUT02` (409), `FILE01`/`FILE02` (413/415), `VAL03` (422, confirm).

### 8.7 Teachers

`Route::apiResource('teachers', TeacherController::class)` + `PATCH teachers/{teacher}/academic-tutor`. `store`/`update` multipart.

| Método | Path | Notas |
|---|---|---|
| GET | `/teachers` | Lista roles `profesor` + `tutor_academico`. Query: `search`, `is_academic_tutor`, `active` |
| GET | `/teachers/{teacher}` | Carga `tutored_groups` si aplica |
| POST | `/teachers` | ver body |
| PUT/PATCH | `/teachers/{teacher}` | mismos campos `sometimes` + `active` (⚠️ `is_academic_tutor` NO editable aquí) |
| DELETE | `/teachers/{teacher}` | baja lógica, `confirm=true` |
| PATCH | `/teachers/{teacher}/academic-tutor` | activar/desactivar como tutor académico y asignar grupos |

**Body `POST /teachers`:**

| Campo | Reglas |
|---|---|
| `first_name` | `required, string, regex:/^[A-Za-zÀ-ÿ\s]{2,45}$/` |
| `second_name` | `sometimes, nullable, string, max:45` |
| `first_surname` | `required, string, max:45` |
| `second_surname` | `required, string, max:45` |
| `email` | `required, email, max:155` |
| `phone` | `required, string, regex:/^\d{10}$/` |
| `birth_date` | `required, date` |
| `gender` | `required, string, in:M,F,OTRO` |
| `photo` | `sometimes, nullable, file` |
| `is_academic_tutor` | `sometimes, boolean` |

Respuesta `AdminTeacherResource`: `id, first_name, second_name, first_surname, second_surname, email, phone, birth_date, gender, active, photo_url, is_academic_tutor, schedules_count, tutored_groups[], temporary_password (solo store)`.

**`PATCH /teachers/{teacher}/academic-tutor`:**

| Campo | Reglas |
|---|---|
| `is_active` | `required, boolean` |
| `group_ids` | `sometimes, array` |
| `group_ids.*` | `integer` (404 `GRP02` si alguno no existe) |

Cambia el `role_id` del usuario entre `profesor`/`tutor_academico` según `is_active`. Si se envían `group_ids`, hace `sync()` **reemplazando** todas las asignaciones de grupo (no additivo). Respuesta ad-hoc: `{ "teacher_id", "is_academic_tutor", "groups": [{id,grade,section}] }`.

**Errores:** `USR01` (404), `USR04` (409, email duplicado), `USR05` (409, baja con horarios activos), `GRP02` (404), `FILE01`/`FILE02`.

### 8.8 Notifications

Rutas manuales (`index`, `show`, `store`, `resend` — **sin** `update`/`destroy`, las notificaciones son inmutables).

**Body `POST /notifications`:**

| Campo | Reglas |
|---|---|
| `title` | `required, string, min:3, max:90` |
| `message` | `required, string, max:350` |
| `type` | `required, in:INASISTENCIA,RETARDO,INCIDENTE,JUSTIFICANTE,RECLAMO,AVISO,RECLAMO_PROFESOR` |
| `target` | `required, in:STUDENT,TUTOR,GROUP,CAREER,ALL` |
| `student_ids` | `required_if:target,STUDENT`, `required_if:target,TUTOR`, `array` |
| `group_ids` | `required_if:target,GROUP`, `array` |
| `career_ids` | `required_if:target,CAREER`, `array` |

Se envía **por tutor familiar activo con la preferencia de notificación correspondiente habilitada** (mapeo `type → preferencia`: `INASISTENCIA→absences`, `RETARDO→lates`, `INCIDENTE→incidents`, `JUSTIFICANTE→justifications`, `RECLAMO`/`RECLAMO_PROFESOR→claims`, resto→`announcements`). Intenta WhatsApp best-effort (no bloqueante). Si no se resuelve ningún destinatario válido → **422 `NOT02`**.

Respuesta `store`/`resend` (ad-hoc, no Resource): `{ "id", "title", "type", "recipients_count", "sent_at" }`.

`GET /notifications` — Query: `type`, `is_read`, `date_from`, `date_to`. `GET /notifications/{id}` — 404 `NOT01`. `POST /notifications/{id}/resend` — body igual a `store` pero todo `sometimes`; si no se manda `target`, reenvía solo al tutor original.

### 8.9 Permissions

Rutas manuales bajo `/users/...`.

`GET /users/permissions` — Query: `search`, `role_id`, `has_overrides`. Lista `AdminUserPermissionsSummaryResource`.

`GET /users/{user}/permissions` — `AdminUserPermissionsResource`: `{ user_id, full_name, email, role{id,name}, role_permissions[], overrides[{id,type,permission}], effective_permissions[] }`. 404 `USR01`.

`POST /users/{user}/permissions/override` — body: `permission_id* (integer)`, `type* (in:PERMITIR,DENEGAR)`. 404 `USR01`/`PERM03`. **409 `PERM04`** si ya existe cualquier regla (PERMITIR o DENEGAR) para ese usuario+permiso — hay que borrar la existente primero.

`DELETE /users/{user}/permissions/override/{override}` — delete físico. 404 `USR01`/`PERM05`. **No** requiere `confirm=true`.

### 8.10 Attendance Settings — tolerancias de asistencia

`Route::apiResource('attendance-settings', AttendanceSettingController::class)`. Parámetro de ruta: `{attendance_setting}`.

| Método | Path | Notas |
|---|---|---|
| GET | `/attendance-settings` | Query: `schedule_id`, `is_active` |
| GET | `/attendance-settings/{attendance_setting}` | |
| POST | `/attendance-settings` | ver body |
| PUT/PATCH | `/attendance-settings/{attendance_setting}` | ver regla de tolerancias |
| DELETE | `/attendance-settings/{attendance_setting}` | baja lógica, **sin** `confirm=true` |

**Body `POST /attendance-settings`:**

| Campo | Reglas |
|---|---|
| `schedule_id` | `required, integer` (404 `SCH01` si no existe) |
| `present_tolerance_minutes` | `required, integer, min:0, max:255` |
| `late_tolerance_minutes` | `required, integer, min:0, max:255, gt:present_tolerance_minutes` |
| `allow_manual_attendance` | `sometimes, boolean` |

Unicidad 1:1 manual por `schedule_id` → **409 `ATS02`** si ya existe.

**Body `PUT/PATCH .../{id}`:** mismos campos `sometimes` (**sin** `gt:` en el FormRequest) + `is_active`. ⚠️ El controlador **recalcula manualmente** `late > present` comparando contra los valores persistidos si no vienen ambos en el payload — si falla: **422 `VAL01`** con `errors: {"late_tolerance_minutes": ["La tolerancia de retardo debe ser mayor a la de asistencia a tiempo."]}`.

Respuesta `AdminAttendanceSettingResource`: `id, schedule_id, present_tolerance_minutes, late_tolerance_minutes, allow_manual_attendance, is_active, schedule{id,subject,group,day_of_week,start_time,end_time}` (solo en `show`/`index` con relación cargada).

**Errores:** `SCH01` (404), `ATS01` (404), `ATS02` (409), `VAL01` (422).

---

## 9. Director de Carrera (`/director-carrera/*`)

**Auth de todo el módulo:** `governance.auth` + `role:director_carrera`.

### 9.1 Alcance (`CareerScope`)

Un director puede tener **más de una carrera** (`careers.director_id` no es único). Todo el módulo filtra por la unión de recursos de todas sus carreras:

- `assertHasCareer()` — usado en `index`/listados: **403 `PERM01`** si el director no tiene ninguna carrera asignada.
- En `show`/detalle: si el recurso no existe → 404 con código propio (`GRP02`, `USR01`, `INC01`, `DEV01`, `CLM01`, `LOG01`); si existe pero es de otra carrera → **403 `PERM01`** (no revela existencia entre carreras).

### 9.2 Groups

| Método | Path | Notas |
|---|---|---|
| GET | `/groups` | Query: `school_year_id`, `shift` |
| GET | `/groups/{group}` | incluye `attendance_summary` (histórico total, sin filtro de fecha) |
| GET | `/groups/{group}/students` | alumnos activos |
| GET | `/groups/{group}/schedule` | horarios activos, array plano |

Respuesta `DirectorGroupResource`: `id, school_year_id, grade, section, shift, is_active, student_count (index), academic_tutors[] (show), attendance_summary{} (show)`.

### 9.3 Students

| Método | Path | Notas |
|---|---|---|
| GET | `/students/{student}` | `StudentProfileResource` |
| GET | `/students/{student}/attendance` | Query: `date_from`, `date_to` (422 `VAL02` si rango inválido) |
| GET | `/students/{student}/justifications` | sin filtros |

### 9.4 Teachers

| Método | Path | Notas |
|---|---|---|
| GET | `/teachers` | `DirectorTeacherResource[]`, `schedules_count` global del profesor |
| GET | `/teachers/{teacher}` | incluye `schedules[]` detallado |
| GET | `/teachers/{teacher}/class-attendance` | Query: `date_from`, `date_to` (default: últimos 7 días), `schedule_id` |

### 9.5 Incidents

Mismo controlador/reglas de negocio que Profesor (sección 10.4) más el flujo de cierre exclusivo de Director:

| Método | Path | Notas |
|---|---|---|
| GET | `/incidents` | paginado, filtros `status`, `severity`, `date_from`, `date_to` |
| GET | `/incidents/active` | solo `ACTIVO`, sin paginar |
| GET | `/incidents/{incident}` | incluye `history` |
| POST | `/incidents` | crea (multipart) |
| PUT | `/incidents/{incident}` | edita (campos `sometimes`) |
| PATCH | `/incidents/{incident}/students` | checklist de emergencia |
| POST | `/incidents/{incident}/close` | **solo Director** |

**Body `POST /incidents`:**

| Campo | Reglas |
|---|---|
| `type` | `required, in:FIRE,GAS,EARTHQUAKE,OTHER` |
| `title` | `required, string, min:3, max:120` |
| `description` | `nullable, string, max:500` |
| `severity` | `required, in:BAJA,MEDIA,ALTA,CRITICA` |
| `schedule_id` | `required, integer, exists:schedules,id` (⚠️ además revalidado contra el scope del director → 403 `PERM01` si el horario existe pero es de otra carrera) |
| `student_ids` | `required, array, min:1` |
| `student_ids.*` | `integer, exists:users,id` |
| `evidence` | `nullable, file` |

**Body `PATCH /incidents/{id}/students`:**

| Campo | Reglas |
|---|---|
| `students` | `required, array, min:1` |
| `students.*.student_id` | `required, integer, exists:users,id` |
| `students.*.status` | `required, in:DESCONOCIDO,PRESENTE,EXTRAVIADO,AUSENTE,SEGURO` |
| `students.*.notes` | `nullable, string, max:255` |

Respuesta ad-hoc: `{ "incident_id", "updated_count" }`.

**Body `POST /incidents/{id}/close`:**

| Campo | Reglas |
|---|---|
| `resolution` | `required, in:RESOLVED,CANCELLED` |
| `notes` | `nullable, string, max:255` (⚠️ se valida pero nunca se persiste) |

Mapea a `status: RESUELTO` o `CANCELADO`. Respuesta ad-hoc: `{ "id", "status", "reviewed_by" }`.

`assertNotClosed`: si `status IN (RESUELTO, CANCELADO)` → **409 `INC03`** en `update`, `updateStudents` y `close`.

**`IncidentDetailResource`:**
```json
{
  "id": 12, "type": "FIRE", "title": "...", "description": "...",
  "severity": "ALTA", "status": "ACTIVO",
  "reporter": { "id": 4, "full_name": "..." },
  "groups": [ { "id": 2, "grade": "3", "section": "A" } ],
  "students": [ { "id": 10, "full_name": "...", "present": false } ],
  "evidence_url": "... | null",
  "history": [
    { "action": "CREATE", "performed_by": {"id":4,"full_name":"..."}, "before": null, "after": {...}, "created_at": "..." }
  ],
  "created_at": "..."
}
```
`students[].present` colapsa `AUSENTE` y `DESCONOCIDO` en `false` — no distingue "aún sin revisar" de "confirmado ausente".

### 9.6 Claims (perspectiva Director)

A diferencia del resto del módulo, **no usa `CareerScope`** — se filtra directo por `claims.director_id` (asignado automáticamente al crear el reclamo, según el director de la carrera del grupo del alumno).

| Método | Path | Notas |
|---|---|---|
| GET | `/claims` | paginado, Query: `group_id`, `status` |
| GET | `/claims/{claim}` | 404 `CLM01` |
| PATCH | `/claims/{claim}/action` | ver body |

**Body `PATCH /claims/{id}/action`:**

| Campo | Reglas |
|---|---|
| `action` | `required, in:EN_PROCESO,CONTACTADO,ACEPTADO,RECHAZADO` |
| `comment` | `nullable, string, max:500` |

Si `status` ya es `ACEPTADO`/`RECHAZADO` (terminal) → **409 `CLM02`**. ⚠️ **Nunca modifica `attendance.status`** — solo cambia `claims.status`.

`TutorClaimResource.history` **siempre es `[]`** (no existe tabla de auditoría de reclamos).

### 9.7 Charts

Todos requieren `assertHasCareer`, sin paginación. Solo `general`/`absences` aceptan `date_from`/`date_to` (⚠️ sin validar rango invertido, a diferencia de otros módulos).

| Endpoint | Respuesta |
|---|---|
| `GET /charts/general` | `{ total_students, attendance_summary{PRESENTE,RETARDO,FALTA,JUSTIFICADA}, attendance_rate }` (`attendance_rate` = PRESENTE / total, no cuenta RETARDO) |
| `GET /charts/incidents` | `{ total, by_severity{}, by_status{} }` (sin filtro de fecha) |
| `GET /charts/absences` | `{ by_group: [{group_id,label,total}], by_subject: [{subject_id,name,total}] }` |
| `GET /charts/justifications` | `{ by_status: {PENDIENTE,ACEPTADO,RECHAZADO} }` (sin fechas) |

### 9.8 Devices

| Método | Path | Notas |
|---|---|---|
| GET | `/devices` | solo dispositivos de aulas con horario activo de la carrera |
| GET | `/devices/{device}` | |
| GET | `/devices/{device}/ping` | mismo comportamiento que Administrador (503 `DEV02` si falla) |

### 9.9 Audit Logs (`/logs/*`)

| Endpoint | Filtra por |
|---|---|
| `GET /logs/students` | `entity='student'`, `entity_id IN studentIds` |
| `GET /logs/teachers` | `entity='teacher'`, `entity_id IN teacherIds` |
| `GET /logs/groups` | `entity='group'`, `entity_id IN groupIds` |
| `GET /logs/devices` | `entity='device'`, `entity_id IN deviceIds` |
| `GET /logs/{log}` | busca sin filtro de entity; 404 `LOG01` si no existe o su `entity` no es una de las 4 soportadas o su `entity_id` está fuera del scope |

Todos paginados (20). `AuditLogResource`: `{ id, entity, entity_id, action (CREATE/UPDATE/DELETE), performed_by{id,full_name}|null, before, after, created_at }`.

⚠️ No hay logs de `incident` ni `claim` expuestos aquí — el historial de incidentes se obtiene vía `GET incidents/{id}` (campo `history`); el de reclamos no existe.

---

## 10. Profesor / Tutor Académico (`/profesor/*`)

**Auth de todo el módulo:** `governance.auth` + `role:profesor,tutor_academico`. ⚠️ **Ambos roles pueden usar todas estas rutas**, no solo `profesor`.

### 10.1 Groups

`GET /groups` — Query: `school_year_id` (si se omite, usa el ciclo `ACTIVO`; si no hay ninguno → 404 `GRP01`). Devuelve los grupos donde el profesor tiene horario activo. `TeacherGroupResource`: `{id, grade, section, shift, career{id,short_name}, student_count}`.

`GET /groups/{group}/students` — 404 `GRP02` si no existe; 403 `PERM01` si el profesor no da clase ahí. `GroupStudentResource[]`.

### 10.2 Schedule

`GET /schedule/today` — Horarios de hoy del profesor, con `session_open` (bool). Array plano, no Resource.

`GET /schedule` — Query: `day` (uno de `LUNES..DOMINGO`; inválido → 422 `VAL01`). Horarios agrupados por día: `{ "LUNES": [...], "MARTES": [...] }`.

### 10.3 Sessions — apertura, registro manual, cierre

`POST /sessions/open` — body: `schedule_id* (integer, min:1, exists:schedules,id)`, `date* (date_format:Y-m-d)`. 403 `PERM01` si el horario no es del profesor. 409 `SES01` si ya existe sesión para ese horario+fecha (en cualquier estado). 404 `DEV01` si el salón no tiene dispositivo activo. Respuesta 201 `ClassSessionResource`: `{session_id, schedule_id, date, status, opened_at}`.

`POST /sessions/{session}/nfc` — registro manual del profesor (lee/teclea el UID). Body: `nfc_uid* (regex igual a /device/nfc)`, `scanned_at* (required, date_format:Y-m-d\TH:i:s)`. 403 `PERM01`, 404 `SES02` (sesión no abierta), 404 `USR02` (UID no reconocido), 403 `ATT02` (grupo incorrecto), 409 `ATT01` (duplicado).

⚠️ **Diferencia clave de tolerancia vs. `/device/nfc`**: aquí la tolerancia PRESENTE/RETARDO se calcula contra `schedule.start_time` (hora **programada** de la clase), mientras que en el tap directo del dispositivo se calcula contra `session.opened_at` (hora **real** de apertura) — pueden dar resultados distintos si la sesión se abrió tarde.

`PATCH /sessions/{session}/students/{student}` — edición manual de un registro. Body: `status* (in:PRESENTE,RETARDO,FALTA)`. ⚠️ No valida que la sesión siga abierta ni que `{student}` exista/pertenezca al grupo — sobreescribe `registered_at` a "ahora" y cambia `method` a `MANUAL` aunque el registro previo fuera `NFC`.

`POST /sessions/{session}/close` — cierre manual. 403 `PERM01`, 409 `SES03` (ya cerrada). Genera `FALTA` (`method: SISTEMA`) para todo alumno del grupo sin registro, notifica ausencias, marca `CERRADA`. Respuesta: `{session_id, status, total_students, on_time, late, absent, closed_at}`.

Esta misma lógica de cierre la ejecuta automáticamente el comando `class-sessions:auto-close` cada 5 minutos (cron), sin `performedByUserId` (queda `null` en auditoría).

⚠️ **`FALTA` nunca se calcula en tiempo real** — solo al cerrar sesión (manual o cron).

### 10.4 Incidents

`GET /incidents/active` — **todos** los incidentes activos del sistema (no solo del profesor autenticado), sin paginar. `IncidentActiveResource[]`.

`GET /incidents` — solo los reportados por el profesor autenticado. Query: `type`, `date_from`, `date_to` (422 `VAL02` si rango inválido). Paginado.

`GET /incidents/{incident}` — 404 `INC01`; 403 `PERM01` si no es el reportante. Incluye `history`.

`POST /incidents` — body (multipart):

| Campo | Reglas |
|---|---|
| `type` | `required, in:FIRE,GAS,EARTHQUAKE,OTHER` |
| `title` | `required, string, min:3, max:120` |
| `description` | `nullable, string, max:500` |
| `severity` | `required, in:BAJA,MEDIA,ALTA,CRITICA` |
| `group_ids` | `nullable, array` |
| `group_ids.*` | `integer, exists:groups,id` |
| `evidence` | `nullable, file` |

⚠️ **Limitación documentada en el propio código**: `incidents.schedule_id` es `NOT NULL` en BD pero este endpoint no recibe `schedule_id`; se usa como "ancla" el primer horario activo del profesor en el primer `group_id` indicado, o cualquiera si no se mandan `group_ids`. Si el profesor no tiene ningún horario activo → 403 `PERM01`. Por cada `group_id`, se adjunta a **todos** los alumnos activos de ese grupo en el checklist con `status: DESCONOCIDO`.

`PUT /incidents/{id}` — campos `sometimes` iguales a `store`. ⚠️ `group_ids` se valida pero **el controlador lo ignora silenciosamente** (no reasigna grupos ni schedule ancla). 403 `PERM01` (solo el creador edita), 409 `INC02` (ya cerrado).

`PATCH /incidents/{id}/students` — body: `students[].student_id* (exists:users,id)`, `students[].present* (boolean)`, `comment` (validado pero no usado). `present:true → PRESENTE`, `present:false → AUSENTE` (nunca `DESCONOCIDO` desde aquí). Respuesta: `{incident_id, updated_students, present_count, absent_count}` (⚠️ los `DESCONOCIDO` no se cuentan en ninguno de los dos totales).

⚠️ Nota: en Profesor, "incidente ya cerrado" usa `error_code INC02` (409); en Director usa `INC03` — mismo significado, código distinto según módulo.

### 10.5 Claims (perspectiva Profesor)

`GET /claims` — solo reclamos sobre asistencias de clases del profesor. Query: `status`, `group_id`. Paginado.

`GET /claims/{claim}` — 404 `CLM01` si no existe o no es suyo. `TeacherClaimResource`: `{id, student{id,full_name,group}, subject{id,name}, description, evidence_url, status, created_at}`.

⚠️ Solo lectura — el profesor **no puede** accionar (`action`) el reclamo, eso es exclusivo de Director (9.6) o Tutor Académico (11).

### 10.6 Students (perspectiva Profesor)

`GET /students/{student}` — 404 `USR01`, 403 `PERM01` si el profesor no tiene horario activo en el grupo del alumno. `StudentProfileResource`.

`GET /students/{student}/attendance` — Query: `date_from`, `date_to` (422 `VAL02`), `subject_id`. Solo asistencias de clases **de este profesor** (aísla las de otros profesores).

`GET /students/{student}/justifications` — solo justificantes de clases de este profesor.

`GET /justifications/{justification}` — detalle de un justificante puntual (motivo, evidencia, estado, revisor). 403 `PERM01` si el justificante no pertenece a un horario de este profesor.

### 10.7 Evento `AttendanceRegistered`

Se dispara en 3 puntos: registro NFC manual del profesor (`performed_by` = profesor), cierre de sesión con `FALTA` autogenerada (`performed_by` = profesor si manual, `null` si cron), y tap directo del dispositivo (`performed_by` = el propio alumno). El listener `WriteAttendanceAuditLog` escribe en `audit_logs` (`entity: attendance`) de forma **síncrona pero best-effort** (si falla, solo loguea un warning, nunca revierte la asistencia ya creada).

---

## 11. Tutor Académico — reclamos y justificantes (`/tutor/*`)

**Auth:** `governance.auth` + `role:tutor_academico` — recuerda: esto es el **profesor-tutor de grupo**, no el tutor familiar (ver sección 1.4).

**Alcance:** `$tutor->academicTutor?->activeGroups()->pluck('groups.id')`. Si el usuario no tiene registro `AcademicTutor` activo o no tiene grupos asignados, las listas simplemente vienen vacías (sin error).

### 11.1 Claims

`GET /claims` — Query: `career_id`, `group_id`, `status` (comparación literal, sin validación). Paginado. `TutorClaimResource[]`.

`GET /claims/{claim}` — 404 `CLM01` si no existe o está fuera de sus grupos.

`PATCH /claims/{claim}/action` — mismo body/lógica que Director (9.6): `action* (in:EN_PROCESO,CONTACTADO,ACEPTADO,RECHAZADO)`, `comment (nullable, max:500)`. 409 `CLM02` si ya es terminal. **No modifica `attendance.status`.**

### 11.2 Justificantes

`PATCH /students/{student}/justifications/{justification}` — revisar (aprobar/rechazar) un justificante.

Body:

| Campo | Reglas |
|---|---|
| `status` | `required, in:ACEPTADO,RECHAZADO` |
| `comment` | `nullable, string, max:300` |

Flujo: 404 `USR01` si `{student}` no existe/no es alumno. 403 `PERM01` si el alumno no está en un grupo activo del tutor. 404 `JUST01` si el justificante no existe/no es de ese alumno. 409 `JUST02` si ya fue revisado (estado terminal, `PENDIENTE` es el único estado editable).

⚠️ **Este es el único punto del sistema donde `attendance.status` cambia como consecuencia de una revisión**: si `status: ACEPTADO`, el backend hace `attendance.update(['status' => 'JUSTIFICADA'])`. Si se rechaza, la asistencia original (`FALTA`) no cambia.

Respuesta: `{justification_id, student_id, status, reviewed_by, reviewed_at, comment}`.

---

## 12. Alumno (`/alumno/*`)

**Auth:** `governance.auth` + `role:alumno`.

### 12.1 Profile

`GET /profile` — `StudentProfileResource` con `group`/`career` cargados.

### 12.2 Claims (perspectiva Alumno)

`GET /claims` — solo los reclamos propios (`claims.tutor_id === user.id`, recordar que `tutor_id` guarda al alumno, no a un tutor familiar). Query: `status`. Paginado. `ClaimResource[]` (distinto de `TutorClaimResource`: expone `teacher`, no `student`/`group`/`career`/`history`).

`GET /claims/{claim}` — ⚠️ usa binding implícito de Eloquent (`Claim $claim`): si el id no existe, Laravel responde 404 **sin el sobre estándar**. Si existe pero no es del alumno → 403 `PERM01` (sí con sobre estándar).

`POST /claims` — el alumno impugna **su falta más reciente** en una materia (no elige `attendance_id`). Body (multipart):

| Campo | Reglas |
|---|---|
| `subject_id` | `required, integer, min:1, exists:subjects,id` |
| `description` | `required, string, min:10, max:500` |
| `evidence` | `nullable, file` (evidencia opcional aquí) |

Flujo: 403 `PERM02` si no está inscrito activamente en esa materia. Toma `$user->attendances()->whereHas('schedule', subject_id)->latest('registered_at')->first()` — ⚠️ **sin filtrar por `status = FALTA`**, toma el registro más reciente sea cual sea su estado. Si no hay ningún registro de asistencia en la materia → 409 `ATT03`. `director_id` se auto-asigna al director de la carrera del grupo del alumno.

⚠️ **Limitación documentada en el código**: el alumno no puede elegir qué falta específica reclama si tiene varias en la misma materia — siempre es la más reciente.

Respuesta 201: `ClaimResource`.

### 12.3 Justifications (perspectiva Alumno)

`GET /justifications` — sin paginar. Query: `subject_id`, `status`.

`GET /justifications/{justification}` — binding implícito (mismo caveat 404 que claims). 403 `PERM01` si no es del alumno.

`POST /subjects/{subject}/attendance/{attendance}/justify` — subir evidencia para justificar una falta puntual. `{subject}`/`{attendance}` son enteros simples (no binding implícito). Body (multipart):

| Campo | Reglas |
|---|---|
| `reason` | `required, string, min:5, max:300` |
| `evidence` | `required, file` (⚠️ aquí SÍ es obligatoria, a diferencia de `claims`) |

Flujo: 404 `ATT03` si `{attendance}` no existe o no es de esa materia. 403 `PERM01` si no es del alumno. **409 `ATT04`** si `attendance.status !== 'FALTA'` (solo se justifican faltas, no retardos). **409 `JUST03`** si ya existe un justificante para esa asistencia (relación 1:1). Crea con `status: PENDIENTE` — **no** cambia `attendance.status` todavía (eso ocurre solo cuando el Tutor Académico aprueba, sección 11.2).

⚠️ **`ATT03` se usa con dos HTTP distintos**: 409 al crear un `claim` sin asistencias; 404 aquí cuando la asistencia puntual no existe/no matchea la materia. Distinguir por endpoint + `status_code`, no solo por `error_code`.

Respuesta 201: `JustificationResource`.

### 12.4 Subjects (perspectiva Alumno)

`GET /subjects` — materias según los horarios activos del grupo del alumno (ciclo escolar `ACTIVO`). `SubjectResource[]`: `{id, name, teacher{id,full_name}, schedule: "Lunes 08:00-09:00, Miércoles 08:00-09:00"}`.

`GET /subjects/{subject}` — binding implícito. Si no hay horario activo del grupo para esa materia → **403 `PERM01`** (⚠️ no 404, aunque la materia simplemente no aplique). `SubjectDetailResource` con `attendance_summary{on_time, late, absent}` (⚠️ `JUSTIFICADA` no se cuenta en ninguno de los 3 buckets).

`GET /subjects/{subject}/attendance` — mismo chequeo 403 `PERM01`. `AttendanceRecordResource[]`: `{attendance_id, date, status, justifiable}` (`justifiable: true` solo si `status === FALTA` y aún no tiene justificante).

---

## 13. Dev tools — solo entorno local (`/dev/*`)

⚠️ **Estas 4 rutas SOLO se registran si `APP_ENV=local`** (`routes/api.php`: `if (app()->environment('local')) { require ... }`). En cualquier otro ambiente (`staging`, `production`, `testing`) **no existen en absoluto** — 404 estándar de Laravel, no un error controlado. **Sin middleware de autenticación** (mismo criterio que `/device/nfc`: son herramientas de prototipo/demo, no producción). Sirven para simular el paso del tiempo sin esperar el horario real de una clase (útil para pruebas y demos en vivo).

### `POST /dev/schedules/{schedule}/activate-now`

Body opcional: `duration_minutes` (int, sin FormRequest — se clampa silenciosamente entre 1 y 480, default 90). **Modifica el `Schedule` real en BD**: `day_of_week = hoy`, `start_time = ahora - 1min`, `end_time = ahora + duration_minutes`, `is_active = true`. Toma el primer `Device` activo del salón. 404 `SCH01` si el horario no existe; 404 sin código si el salón no tiene dispositivo activo. Respuesta incluye `hint` con instrucciones para el siguiente paso (`POST /device/nfc`).

### `POST /dev/schedules/{schedule}/reset-session`

Sin body. Borra **todos** los `ClassSession` de ese horario (cascada elimina las `attendances` asociadas vía FK). 404 `SCH01`. Respuesta: `{schedule_id, sessions_deleted}`.

### `GET /dev/schedules/{schedule}/status`

Sin body. Muestra el estado actual del horario + la sesión de hoy (si existe) con sus asistencias. 404 `SCH01`.

### `POST /dev/class-sessions/{class_session}/close-now`

Sin body. Cierra la sesión inmediatamente reutilizando `CloseClassSessionService` (genera `FALTA` para quien no fichó, notifica, marca `CERRADA`). 404 sin código si no existe; 409 sin código si ya estaba cerrada. Misma forma de respuesta que `POST /profesor/sessions/{id}/close`.

---

## 14. Notas transversales y gotchas importantes

1. **El Bearer token es de Gobernanza, no de Sanctum local.** Se revalida en cada request (con caché de 120s) contra un servicio externo.
2. **`error_code` no siempre está presente** en las respuestas de error — puede estar ausente por completo. No lo uses como único criterio de manejo de errores; combínalo con `status_code`.
3. **`POST /auth/login` y `POST /auth/users`** rompen el contrato estándar: no usan el sobre `{success,...}`.
4. **Endpoints con route-model-binding implícito** (`Alumno\ClaimController@show`, `JustificationController@show`, `SubjectController@show/attendance`) dan 404 en formato de Laravel puro si el ID no existe, distinto al resto de la API.
5. **`ATT03` tiene dos HTTP status distintos** según el endpoint (409 en creación de claim, 404 en creación de justificante).
6. **`INC02` (Profesor) vs. `INC03` (Director)** significan lo mismo ("incidente ya cerrado") con código distinto según el módulo que lo lanza.
7. **Tolerancia PRESENTE/RETARDO tiene dos referencias horarias distintas** según el canal: `session.opened_at` en el tap directo del dispositivo (`/device/nfc`), vs. `schedule.start_time` en el registro manual del profesor (`/profesor/sessions/{id}/nfc`). **`FALTA` nunca se calcula en el momento del tap** — solo al cerrar la sesión (manual o cron cada 5 min).
8. **`claims.tutor_id` guarda al alumno reclamante**, no a un tutor familiar. El módulo `/tutor/*` es en realidad para el **tutor académico** (profesor), no el tutor familiar (que no tiene login).
9. **Los reclamos (`claims`) nunca modifican `attendance.status`**, ni siquiera al ser `ACEPTADO`. Solo los **justificantes** aprobados por el tutor académico cambian la asistencia a `JUSTIFICADA`.
10. **`IncidentDetailResource.students[].present`** colapsa `AUSENTE` y `DESCONOCIDO` en `false` — no se puede distinguir "aún sin revisar" de "confirmado ausente" sin el detalle del pivot.
11. **`TutorClaimResource.history` siempre es `[]`** — no existe (todavía) auditoría de reclamos, a diferencia de incidentes que sí tienen `history` completo vía `audit_logs`.
12. **La mayoría de "eliminar" en Administrador es baja lógica** (`is_active`/`active = false`, campo inconsistente entre entidades). `confirm=true` es obligatorio solo en Career/Group/Subject/Student/Teacher, no en Device/AttendanceSetting/PermissionOverride.
13. **Ningún endpoint de Administrador ni Director de "listados sin detalle" pagina**, salvo los explícitamente marcados arriba como "paginado" (incidents, claims, audit logs). El resto usa `->get()` completo.
14. **`GET /dev/*` y `POST /dev/*` desaparecen fuera de `APP_ENV=local`** — no construyas flujos de producción que dependan de ellos.
