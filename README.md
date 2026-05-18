# Guía de Inicio Rápido (Entorno Local)

Este proyecto está configurado para correr de forma nativa en tu máquina local (sin Docker).

## Requisitos Previos
Asegúrate de tener instalado globalmente en tu sistema:
* **PHP 8.4** o superior
* **Composer** (Gestor de dependencias de PHP)
* **MySQL** (ya sea nativo, o mediante herramientas como Laragon, XAMPP o MAMP)

---

## Pasos para la Inicialización

Sigue estos pasos en orden desde la terminal de tu proyecto:

### 1. Configurar el entorno (.env)
Duplica el archivo de ejemplo para crear tu configuración real:
```bash
cp .env.example .env
```

### 2. Instalar dependencias de PHP
Descarga todas las librerías del framework y los paquetes adicionales (como Scribe):

```bash
composer install
```

### 3. Generar la clave de seguridad
Crea la firma única de encriptación para tu aplicación:

```bash
php artisan key:generate
```

### 4. Ejecutar las migraciones
Crea la estructura de tablas y el sistema de tokens para la API en tu base de datos:

```bash
php artisan migrate
```

### 5. Generar la documentación (Scribe)
Compila la documentación interactiva de la API basada en tus controladores:

```bash
php artisan scribe:generate
```

---

Ruta base de la API: http://127.0.0.1:8000/api

Ver Documentación Interactiva: http://127.0.0.1:8000/docs/index.html