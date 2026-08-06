# Integración ESP32 — Registro de asistencia por NFC

> Un solo endpoint,
> sin login ni tokens — el ESP32 solo necesita mandar su `mac_address` (la de fábrica) y
> el UID que lea de la tarjeta.

## El endpoint

```
POST {BASE_URL}/api/v1/device/nfc
Content-Type: application/json
```

En local: `BASE_URL = http://localhost:8000`. No lleva ningún header de autenticación.

### Body que manda el ESP32

```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "nfc_uid": "04:A1:B2:C3",
  "scanned_at": "2026-08-06T08:15:00"
}
```

| Campo | Obligatorio | Formato | Notas |
|---|---|---|---|
| `mac_address` | Sí | `AA:BB:CC:DD:EE:FF` (hex, 6 pares separados por `:`) | La MAC del propio ESP32 |
| `nfc_uid` | Sí | hex, `:`, `-` o espacios, hasta 100 caracteres | El UID tal cual lo lee el lector |
| `scanned_at` | No | `AAAA-MM-DDTHH:MM:SS` | Si el ESP32 no tiene reloj confiable, se puede omitir — el servidor usa su propia hora |

**No hace falta saber nada más** (ni quién es el profesor, ni qué sesión está abierta,
ni el `classroom_id`): la API resuelve todo internamente a partir de `mac_address` +
`nfc_uid`.

## Qué puede pasar cuando se tapea una tarjeta

El mismo endpoint sirve para dos cosas distintas, según de quién sea la tarjeta:

1. **Tarjeta del profesor** → abre la clase (arranca el conteo de tolerancia).
2. **Tarjeta de un alumno** → registra su asistencia en la clase ya abierta.

La API decide cuál de las dos es sola con el UID — el ESP32 no necesita distinguir.

### Respuesta cuando abre la clase (tarjeta del profesor)

`201 Created`
```json
{
  "success": true,
  "status_code": 201,
  "message": "Sesión de clase abierta correctamente.",
  "data": {
    "event": "session_opened",
    "session_id": 88,
    "opened_at": "2026-08-06T08:15:00+00:00"
  }
}
```
Sugerencia para la pantallita: **"Clase iniciada"** (verde).

### Respuesta cuando registra asistencia (tarjeta de alumno)

`201 Created`
```json
{
  "success": true,
  "status_code": 201,
  "message": "Asistencia registrada correctamente.",
  "data": {
    "event": "attendance_registered",
    "student_id": 101,
    "full_name": "Juan Ramírez Torres",
    "status": "PRESENTE",
    "checked_in_at": "2026-08-06T08:17:00+00:00"
  }
}
```
`data.status` puede ser `"PRESENTE"` o `"RETARDO"` — con eso se decide el color/ícono en
pantalla (ej. verde para `PRESENTE`, amarillo para `RETARDO`). `data.full_name` es lo que
se muestra en la pantallita para confirmar quién pasó.

## Errores (todo trae `status_code` HTTP + `error_code` para diferenciarlos)

| HTTP | `error_code` | Mensaje | Cuándo pasa | Sugerencia en pantalla |
|---|---|---|---|---|
| 404 | `DEV01` | El dispositivo solicitado no existe. | La `mac_address` no está registrada en la API | "Dispositivo no registrado" |
| 403 | `DEV04` | El dispositivo ya se encuentra dado de baja. | El device existe pero está desactivado | "Dispositivo desactivado" |
| 404 | `SES04` | No hay una clase programada en este salón en este momento. | Nadie tiene horario en ese salón ahora mismo | "Sin clase en este horario" |
| 404 | `USR02` | Tarjeta NFC no reconocida. | El UID leído no está registrado a ningún usuario | "Tarjeta no reconocida" |
| 409 | `SES01` | Ya existe una sesión abierta para esta clase. | El profesor vuelve a tapear con la clase ya abierta (no pasa nada, es informativo) | "La clase ya está iniciada" |
| 409 | `SES03` | La sesión ya fue cerrada anteriormente. | El profesor tapea de nuevo pero esa clase de hoy ya se cerró | "Esta clase ya terminó" |
| 403 | `ATT02` | Este alumno no pertenece al grupo de esta clase. | Un alumno de otro grupo tapea en un salón que no le toca | "Alumno no pertenece al grupo" |
| 404 | `SES02` | La sesión de clase no existe o ya fue cerrada. | Un alumno tapea antes de que el profesor haya abierto la clase | "Espera a que el profesor inicie la clase" |
| 409 | `ATT01` | Este alumno ya registró su asistencia en esta sesión. | El mismo alumno tapea dos veces en la misma clase | "Ya registraste tu asistencia" |
| 422 | `VAL01` | Datos inválidos. Revisa los campos marcados. | `mac_address`/`nfc_uid` mal formados (revisar `errors` en la respuesta) | — (error de firmware, no debería pasar en producción) |

Forma general de un error:
```json
{
  "success": false,
  "status_code": 404,
  "message": "Tarjeta NFC no reconocida.",
  "error_code": "USR02",
  "data": null,
  "errors": null
}
```
Para la pantallita basta con leer `error_code` (o `message` si prefieren mostrar texto
tal cual viene de la API, ya está en español).

## Sobre cerrar la clase

El ESP32 **no** necesita hacer nada para cerrar la clase — eso lo hace un proceso
automático del servidor que revisa cada 5 minutos y cierra las clases cuya hora de fin
ya pasó (marca falta a quien no haya registrado). No hay un endpoint de "cerrar" que el
dispositivo tenga que llamar.

## Ejemplo rápido para probar con curl

```bash
curl -X POST http://localhost:8000/api/v1/device/nfc \
  -H "Content-Type: application/json" \
  -d '{"mac_address":"AA:BB:CC:DD:EE:FF","nfc_uid":"04:A1:B2:C3"}'
```
