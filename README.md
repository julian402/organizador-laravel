# Organizador

Aplicación web para organizar tareas y actividades. Laravel 12 (MVC) + PostgreSQL. Preparada para desplegarse en AWS (2 EC2 Ubuntu: app y BD).


## Estructura

```
Organizador/
├── src/                 Aplicación Laravel (MVC)
│   ├── app/Models/                  Modelo User
│   ├── app/Http/Controllers/        RegisterController, LoginController, DashboardController
│   ├── app/Http/Requests/Auth/      Validaciones (RegisterRequest, LoginRequest)
│   ├── resources/views/             Vistas Blade
│   ├── database/migrations/         Esquema de BD
│   ├── routes/web.php               Rutas
│   ├── tests/Feature/Auth/          Pruebas CP-01 a CP-14
│   ├── Dockerfile                   Contenedor de la app (PHP 8.3 + Apache)
│   └── docker/entrypoint.sh         Arranque del contenedor
├── docs/
│   ├── 01-arquitectura.md           MVC, rutas, arquitectura AWS
│   ├── 02-modelo-bd.md              Modelo E-R y diccionario de datos
│   ├── 03-diseno-funcional.md       Diagramas de clases y de secuencia
│   └── 04-pruebas-y-trazabilidad.md Casos de prueba y matriz de trazabilidad
└── infra/aws/           Scripts de despliegue (Ubuntu 24.04) — aún no se usan
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
| `/login` | HU-02 |
| `/dashboard` | Área privada |
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

Usan la BD `organizador_test` y no tocan los datos de la app.

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

## Despliegue en AWS

Ver [`infra/aws/README.md`](infra/aws/README.md). Lo único que cambia respecto a local es el `.env`.
