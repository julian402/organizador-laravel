# 1. Arquitectura

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) — patrón **MVC** |
| Vistas | Blade + CSS propio (`public/css/app.css`), sin build de npm |
| Base de datos | PostgreSQL 17 |
| Autenticación | Guard `web` de Laravel (sesiones en BD, contraseñas con bcrypt) |
| Servidor (AWS) | Ubuntu Server 24.04 LTS + Nginx + PHP-FPM |

## MVC en el proyecto

```mermaid
flowchart LR
    U[Navegador] -->|HTTP| R[routes/web.php]
    R --> MW{Middleware<br/>guest / auth}
    MW --> C[Controller<br/>RegisterController<br/>LoginController<br/>DashboardController]
    C --> FR[Form Request<br/>RegisterRequest<br/>LoginRequest]
    C --> M[Model<br/>User]
    M --> DB[(PostgreSQL)]
    C --> V[View Blade<br/>auth/register<br/>auth/login<br/>dashboard]
    V --> U
```

| Elemento | Archivo |
|---|---|
| Rutas | `src/routes/web.php` |
| Modelo | `src/app/Models/User.php` |
| Controladores | `src/app/Http/Controllers/Auth/RegisterController.php`, `LoginController.php`, `DashboardController.php` |
| Validaciones | `src/app/Http/Requests/Auth/RegisterRequest.php`, `LoginRequest.php` |
| Vistas | `src/resources/views/layouts/app.blade.php`, `auth/register.blade.php`, `auth/login.blade.php`, `dashboard.blade.php` |
| Migraciones | `src/database/migrations/` |
| Pruebas | `src/tests/Feature/Auth/RegistroTest.php`, `LoginTest.php` |

## Rutas

| Método | URL | Acceso | Descripción | HU |
|---|---|---|---|---|
| GET | `/` | público | Página de inicio | — |
| GET | `/salud` | público | "Hola mundo" en JSON para verificar despliegue | infra |
| GET | `/up` | público | Health check nativo de Laravel | infra |
| GET/POST | `/registro` | solo visitantes | Formulario y registro | HU-01 |
| GET/POST | `/login` | solo visitantes | Formulario e inicio de sesión | HU-02 |
| GET | `/dashboard` | autenticado | Área privada | HU-02 |
| POST | `/logout` | autenticado | Cierre de sesión básico (se completa en HU-04) | HU-04 |

## Despliegue objetivo en AWS (2 máquinas)

```mermaid
flowchart LR
    I((Internet)) -->|80 / 443| APP
    subgraph VPC
      subgraph "EC2 #1 — App (Ubuntu 24.04)"
        APP[Nginx] --> FPM[PHP-FPM 8.3<br/>Laravel]
      end
      subgraph "EC2 #2 — BD (Ubuntu 24.04)"
        PG[(PostgreSQL)]
      end
      FPM -->|5432 solo desde<br/>Security Group de la App| PG
    end
```

- **Local:** todo en el mismo equipo (`DB_HOST=127.0.0.1`).
- **AWS:** solo cambia el `.env` (`DB_HOST=<IP privada de la EC2 de BD>`). El código es el mismo.
- Ver `infra/aws/README.md` para los pasos.
