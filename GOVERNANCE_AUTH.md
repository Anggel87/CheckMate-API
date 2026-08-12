# Governance Auth Contract

Gobernanza es el servicio central de identidad. Las apps externas no registran usuarios publicamente ni validan contrasenas por su cuenta.

## Clientes Confiables

Toda llamada sistema-a-sistema usa:

```http
X-Client-Id: governance-mobile-local
X-Client-Secret: governance-mobile-secret
```

En Docker estos valores salen de `.env.docker` y se crean con `php artisan migrate --seed`.

## Crear Usuario Desde La API Principal

Cuando la app principal crea un alumno, profesor, administrador escolar o director, debe crear primero o en la misma transaccion de negocio la identidad en gobernanza:

```http
POST /api/v1/internal/users
Content-Type: application/json
X-Client-Id: governance-web-local
X-Client-Secret: governance-web-secret
```

```json
{
  "name": "Carlos Lopez",
  "email": "carlos.lopez@example.edu",
  "role": "profesor",
  "active": true
}
```

Roles soportados (`key_name` en la tabla `roles` de gobernanza; coinciden 1:1 con `roles.name` de CheckMate-API):

- `profesor`
- `tutor_academico`
- `alumno`
- `administrador`
- `director_carrera`

## Contrasena Temporal

Si la API principal no envia `password`, gobernanza genera una `temporary_password` y la devuelve una sola vez en la respuesta de creacion.

Esta contrasena sirve para el primer acceso del usuario. El flujo esperado es:

1. El administrador crea al profesor, alumno, administrador escolar o director desde la app principal.
2. La app principal llama a gobernanza sin `password`.
3. Gobernanza crea el usuario y devuelve `temporary_password`.
4. La app principal entrega esa contrasena al usuario por el canal definido por el proyecto.
5. El usuario inicia sesion en web o movil con su correo y esa contrasena temporal.
6. En una siguiente etapa, la app deberia obligar al usuario a cambiar esa contrasena despues del primer acceso.

La `temporary_password` no debe guardarse en texto plano en la app principal. Solo debe usarse para comunicar la credencial inicial al usuario.

```json
{
  "message": "Usuario creado en gobernanza.",
  "data": {
    "user": {
      "id": 42,
      "name": "Carlos Lopez",
      "email": "carlos.lopez@example.edu",
      "role": "profesor"
    },
    "temporary_password": "abc123..."
  }
}
```

La app principal debe guardar el `user.id` como `governance_user_id` en su entidad de dominio.

## Login Movil/API

```http
POST /api/v1/auth/login
Content-Type: application/json
X-Client-Id: governance-mobile-local
X-Client-Secret: governance-mobile-secret
```

```json
{
  "email": "carlos.lopez@example.edu",
  "password": "abc123...",
  "device_name": "android"
}
```

Respuesta:

```json
{
  "message": "Login exitoso.",
  "data": {
    "token": "1|...",
    "token_type": "Bearer",
    "user": {
      "id": 42,
      "name": "Carlos Lopez",
      "email": "carlos.lopez@example.edu",
      "role": "profesor"
    }
  }
}
```

## Popup Web

La app web abre:

```text
/governance/auth?client_id=governance-web-local&redirect_uri=https://app.example.com/auth/callback
```

Al autenticar, gobernanza envia:

```js
window.opener.postMessage({
  type: 'governance_auth',
  data: { token, token_type, user }
}, '*')
```

Si no existe `window.opener`, redirige a `redirect_uri` con `token` y `token_type`.

## Sesion

Endpoints protegidos con `Authorization: Bearer {token}`:

- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/refresh`
- `GET /api/v1/auth/me`

## Verificar Token Desde La API Principal

Cada endpoint protegido de la app principal recibe `Authorization: Bearer {token}` del
cliente (emitido por gobernanza en el login). Para saber a que usuario pertenece ese
token, la app principal llama:

```http
GET /api/v1/auth/me
Authorization: Bearer {token}
Accept: application/json
```

Respuesta:

```json
{
  "message": "Usuario autenticado.",
  "data": {
    "user": {
      "id": 42,
      "name": "Carlos Lopez",
      "email": "carlos.lopez@example.edu",
      "role": "profesor"
    }
  }
}
```

Si el token es invalido, expirado o ausente, gobernanza responde `401`.

Flujo esperado en la app principal:

1. Recibe `Authorization: Bearer {token}` en el request entrante.
2. Llama a `GET /api/v1/auth/me` con ese mismo token.
3. Si gobernanza responde `401`, la app principal rechaza el request (sesion invalida).
4. Si responde `200`, usa `data.user.id` para buscar el usuario local por
   `governance_user_id`.
