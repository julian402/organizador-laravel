# Organizador

Aplicación web para organizar tareas y actividades. Laravel 12 (MVC) + PostgreSQL, desplegada en AWS (2 EC2 Ubuntu: app y BD).

**En producción:** https://organizador-laravel.duckdns.org

## Funcionalidades

| Sprint | Historia | Qué hace |
|---|---|---|
| 1 | HU-01 Registro | Crear cuenta con validación de correo y contraseña segura (mín. 8, mayúscula, minúscula y número) |
| 1 | HU-02 Inicio de sesión | Login con sesión en BD y regeneración de sesión |
| 2 | HU-03 Recuperar contraseña | Enlace por correo de un solo uso que expira a los 30 min; la nueva contraseña debe ser distinta de la actual; mensaje genérico exista o no el correo |
| 2 | HU-04 Cerrar sesión | Invalida la sesión en el servidor; sin acceso con el botón Atrás (`Cache-Control: no-store`) |
| 2 | HU-05 Límite de intentos | 5 fallos por correo + IP en 15 min bloquean el login 15 min |

Seguridad transversal: CSRF, cabeceras (CSP, X-Frame-Options, HSTS en HTTPS), límites por IP en login/registro/recuperación, cookies `httponly` (y `secure` en producción).


## Estructura

```
Organizador/
├── src/                 Aplicación Laravel (MVC)
│   ├── app/Models/                  Modelo User
│   ├── app/Http/Controllers/        Register, Login, ForgotPassword, ResetPassword, Dashboard
│   ├── app/Http/Requests/Auth/      Validaciones (Register, Login, ForgotPassword, ResetPassword)
│   ├── app/Http/Middleware/         SecurityHeaders, SinCache
│   ├── app/Notifications/           RestablecerContrasena (correo de recuperación)
│   ├── resources/views/             Vistas Blade
│   ├── database/migrations/         Esquema de BD
│   ├── routes/web.php               Rutas
│   ├── tests/Feature/               Pruebas funcionales CP-01 a CP-42
│   ├── tests/Unit/                  Pruebas unitarias UT-01 a UT-10
│   ├── Dockerfile                   Contenedor de la app (PHP 8.3 + Apache)
│   └── docker/entrypoint.sh         Arranque del contenedor
├── docs/
│   ├── 01-arquitectura.md           MVC, rutas, arquitectura AWS
│   ├── 02-modelo-bd.md              Modelo E-R y diccionario de datos
│   ├── 03-diseno-funcional.md       Diagramas de clases y de secuencia
│   └── 04-pruebas-y-trazabilidad.md Casos de prueba y matriz de trazabilidad
└── infra/aws/           Scripts y guía de despliegue en AWS (Ubuntu 26.04)
```

Los diagramas están en Mermaid: se ven en GitHub o en VS Code (extensión *Markdown Preview Mermaid Support*).

## Requisitos locales (Windows)

- PHP 8.2+ (se usa el de XAMPP: `C:\xampp\php\php.exe`, con `pdo_pgsql`, `pgsql`, `zip`, `intl` activos)
- Composer (`C:\Users\corre\bin\composer.bat`)
- PostgreSQL 17 (servicio `postgresql-x64-17`, puerto 5432)

## Correr en local

```powershell
cd Organizador\src
C:\xampp\php\php.exe artisan migrate     # solo la primera vez o cuando haya migraciones nuevas
C:\xampp\php\php.exe artisan serve
```

Abrir http://127.0.0.1:8000

| URL | Qué es |
|---|---|
| `/` | Inicio |
| `/registro` | HU-01 |
| `/login` | HU-02 y HU-05 (límite de intentos) |
| `/recuperar` | HU-03 (el correo de recuperación se escribe en `src/storage/logs/laravel.log` con `MAIL_MAILER=log`) |
| `/dashboard` | Área privada (botón "Cerrar sesión", HU-04) |
| `/salud` | "Hola mundo" (verificación de despliegue) |

## Base de datos local

| | |
|---|---|
| Host | 127.0.0.1:5432 |
| BD app | `organizador` |
| BD pruebas | `organizador_test` |
| Usuario / clave | `organizador` / `organizador` |
| Superusuario | `postgres` / `postgres` |

Ver datos: `"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U organizador -h 127.0.0.1 -d organizador` (o pgAdmin).

## Pruebas

```powershell
cd Organizador\src
C:\xampp\php\php.exe artisan test
```

Usan la BD `organizador_test` y no tocan los datos de la app. Resultado actual: **73 pruebas aprobadas**: 60 funcionales (Sprint 1: CP-01 a CP-14, Sprint 2: CP-15 a CP-42) y 13 unitarias (UT-01 a UT-10 del Sprint 2). Solo unitarias: `artisan test --testsuite Unit`. Detalle y matriz de trazabilidad en [`docs/04-pruebas-y-trazabilidad.md`](docs/04-pruebas-y-trazabilidad.md).

## Contenedor de la aplicación (Docker)

La imagen contiene solo la app; la BD queda fuera (local: PostgreSQL de Windows; AWS: la EC2 de BD).

Primera vez: `copy src\.env.docker.example src\.env.docker` y completar `APP_KEY` (puede ser el del `.env`) y `DB_PASSWORD`.

Desde la carpeta `Organizador`:

| Acción | Comando |
|---|---|
| Levantar viendo los logs en vivo (Ctrl+C detiene) | `docker compose up --build` |
| Levantar en segundo plano | `docker compose up -d --build` |
| Ver logs en vivo | `docker compose logs -f app` |
| Ver estado | `docker compose ps` |
| Entrar al contenedor | `docker compose exec app bash` |
| Comandos artisan | `docker compose exec app php artisan migrate` |
| Reiniciar | `docker compose restart app` |
| Detener y borrar | `docker compose down` |

Probar: http://127.0.0.1:8080/salud — los logs muestran cada petición de Apache y los errores de Laravel (`LOG_CHANNEL=stderr`).

## Producción (AWS)

| | |
|---|---|
| URL | https://organizador-laravel.duckdns.org |
| Verificación | https://organizador-laravel.duckdns.org/salud |
| Región | us-east-2 (Ohio) |
| App | EC2 Ubuntu 26.04 · Nginx + PHP 8.5-FPM · Elastic IP (fija) |
| BD | EC2 Ubuntu 26.04 · PostgreSQL 18 · solo accesible desde la app |
| Dominio | DuckDNS (gratuito) |
| HTTPS | Let's Encrypt con certbot (renovación automática, TLS 1.2/1.3) |
| Correo | Gmail SMTP con contraseña de aplicación |

Las claves (BD, APP_KEY, correo) solo existen en el `.env` del servidor; nunca en el repositorio.

Guía paso a paso y cómo actualizar: [`infra/aws/README.md`](infra/aws/README.md). Lo único que cambia respecto a local es el `.env`.
