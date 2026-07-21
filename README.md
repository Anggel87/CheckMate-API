# CheckMate API - Entorno Docker

Se agregaron los archivos Docker necesarios y se validó que el proyecto levanta correctamente.

---

## Archivos Creados

- `Dockerfile`
- `docker-compose.yml`
- `.dockerignore`
- `.env.docker`
- `docker/entrypoint.sh`

---

## Incluye

- PHP `8.4`
- Node `22`
- Composer dentro del contenedor
- npm dependencies dentro de volumen Docker
- Composer dependencies dentro de volumen Docker
- MySQL `8.4`
- Migraciones automáticas al arrancar
- Limpieza de caches de Laravel para evitar usar configuración local cacheada
- `.env.docker` separado para no depender de tu `.env` local

---

## Qué hace el `entrypoint.sh` automáticamente

Al levantar el contenedor por primera vez, el script se encarga de todo sin intervención manual:

1. Copia `.env.example` a `.env` si no existe.
2. Instala dependencias de Composer si `vendor/` está vacío o no existe.
3. Instala dependencias de npm si `node_modules/` está vacío o no existe.
4. Limpia caches de Laravel (`config`, `route`, `view`, `event`).
5. Genera la `APP_KEY` automáticamente si no está definida en el `.env`.
6. Espera a que MySQL esté disponible antes de continuar.
7. Ejecuta las migraciones (`migrate --force`).

**En resumen: solo necesitas levantar el contenedor, no hay pasos manuales de configuración inicial.**


---

## Comandos Útiles

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan test --compact
docker compose down
```
