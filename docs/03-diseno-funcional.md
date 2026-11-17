# 3. Diseño funcional (clases y secuencias)

## Diagrama de clases

```mermaid
classDiagram
    class Controller
    class RegisterController {
        +create() View
        +store(RegisterRequest) RedirectResponse
    }
    class LoginController {
        +create() View
        +store(LoginRequest) RedirectResponse
        +destroy(Request) RedirectResponse
    }
    class DashboardController {
        +__invoke(Request) View
    }
    class FormRequest
    class RegisterRequest {
        #prepareForValidation() void
        +rules() array
        +messages() array
    }
    class LoginRequest {
        +MAX_INTENTOS = 5
        +MINUTOS_VENTANA = 15
        +MINUTOS_BLOQUEO = 15
        #prepareForValidation() void
        +rules() array
        +messages() array
        +authenticate() void
        -asegurarQueNoEstaBloqueado() void
        -registrarIntentoFallido() void
        -claveIntentos() string
        -claveBloqueo() string
    }
    class ForgotPasswordController {
        +create() View
        +store(ForgotPasswordRequest) RedirectResponse
    }
    class ResetPasswordController {
        +create(Request, token) View
        +store(ResetPasswordRequest) RedirectResponse
    }
    class ForgotPasswordRequest {
        +rules() array
    }
    class ResetPasswordRequest {
        +rules() array
    }
    class ValidaContrasena {
        <<trait>>
        #reglasContrasena() array
        #mensajesContrasena() array
    }
    class RestablecerContrasena {
        <<Notification>>
        +string token
        +toMail(User) MailMessage
        +url(User) string
    }
    class SinCache {
        <<Middleware>>
        +handle(Request, Closure) Response
    }
    class User {
        +int id
        +string name
        +string email
        +string password
        #casts() array
        #email() Attribute
        +sendPasswordResetNotification(token) void
    }
    class Authenticatable

    Controller <|-- RegisterController
    Controller <|-- LoginController
    Controller <|-- DashboardController
    Controller <|-- ForgotPasswordController
    Controller <|-- ResetPasswordController
    FormRequest <|-- RegisterRequest
    FormRequest <|-- LoginRequest
    FormRequest <|-- ForgotPasswordRequest
    FormRequest <|-- ResetPasswordRequest
    RegisterRequest ..> ValidaContrasena : usa
    ResetPasswordRequest ..> ValidaContrasena : usa
    Authenticatable <|-- User
    RegisterController ..> RegisterRequest : usa
    RegisterController ..> User : crea
    LoginController ..> LoginRequest : usa
    LoginController ..> User : autentica (Auth)
    ForgotPasswordController ..> ForgotPasswordRequest : usa
    ResetPasswordController ..> ResetPasswordRequest : usa
    ResetPasswordController ..> User : cambia contraseña
    User ..> RestablecerContrasena : envía
    DashboardController ..> User : muestra
    SinCache ..> DashboardController : protege
```

## Secuencia HU-01 — Registro

```mermaid
sequenceDiagram
    actor V as Visitante
    participant N as Navegador
    participant R as Rutas (guest)
    participant C as RegisterController
    participant F as RegisterRequest
    participant U as User (Modelo)
    participant DB as PostgreSQL

    V->>N: Abre /registro
    N->>R: GET /registro
    R->>C: create()
    C-->>N: Vista auth/register
    V->>N: Llena nombre, correo, contraseña, confirmación
    N->>R: POST /registro (+ token CSRF)
    R->>F: Valida (correo a minúsculas)
    F->>DB: ¿existe el correo? (unique)
    alt Datos inválidos (CA-01.2 a CA-01.5)
        F-->>N: Redirige atrás con errores
    else Datos válidos
        F->>C: store()
        C->>U: User::create()
        U->>U: Hash bcrypt de la contraseña (CA-01.7)
        U->>DB: INSERT INTO users
        C-->>N: Redirige a /login con mensaje (CA-01.6)
    end
```

## Secuencia HU-02 — Inicio de sesión

```mermaid
sequenceDiagram
    actor U as Usuario
    participant N as Navegador
    participant R as Rutas
    participant C as LoginController
    participant F as LoginRequest
    participant A as Auth (guard web)
    participant DB as PostgreSQL

    U->>N: Abre /login
    N->>R: GET /login
    R->>C: create()
    C-->>N: Vista auth/login
    U->>N: Ingresa correo y contraseña
    N->>R: POST /login
    R->>F: Valida campos obligatorios (CA-02.4)
    alt Campos vacíos
        F-->>N: Errores de validación
    else Campos completos
        F->>C: store()
        C->>A: attempt(email, password)
        A->>DB: SELECT usuario por email
        A->>A: Hash::check(password)
        alt Credenciales incorrectas
            C-->>N: Mensaje genérico (CA-02.3)
        else Credenciales correctas
            C->>C: session()->regenerate()
            C-->>N: Redirige a /dashboard (CA-02.5)
        end
    end

    Note over N,R: Visitante sin sesión pide /dashboard
    N->>R: GET /dashboard
    R-->>N: middleware auth → redirige a /login (CA-02.6)
```

## Secuencia HU-03 — Recuperación de contraseña

```mermaid
sequenceDiagram
    actor U as Usuario
    participant N as Navegador
    participant FC as ForgotPasswordController
    participant B as Password Broker
    participant DB as PostgreSQL
    participant M as Correo
    participant RC as ResetPasswordController

    U->>N: Abre /recuperar (CA-03.1)
    N->>FC: POST /recuperar (correo)
    FC->>B: sendResetLink(correo)
    B->>DB: SELECT usuario por correo
    alt La cuenta existe
        B->>B: Token aleatorio de 64 caracteres
        B->>DB: Guarda hash(token) + created_at en password_reset_tokens
        B->>M: RestablecerContrasena (enlace con APP_URL) (CA-03.3)
    end
    FC-->>N: Mismo mensaje genérico en ambos casos (CA-03.2)

    U->>N: Abre el enlace /restablecer/{token}?email=
    N->>RC: GET → muestra formulario o aviso de enlace vencido
    U->>N: Escribe nueva contraseña + confirmación
    N->>RC: POST /restablecer
    RC->>RC: Valida reglas de HU-01 (CA-03.5)
    RC->>B: reset(email, token, contraseña)
    B->>DB: ¿hash coincide y created_at < 30 min? (CA-03.4)
    alt Token válido
        B->>DB: UPDATE users.password (hash) (CA-03.7)
        B->>DB: DELETE token (un solo uso, CA-03.6)
        RC->>DB: DELETE sesiones abiertas del usuario
        RC-->>N: Redirige a /login con mensaje
    else Token inválido, vencido o ya usado
        RC-->>N: "El enlace no es válido o ya expiró"
    end
```

## Secuencia HU-04 — Cierre de sesión

```mermaid
sequenceDiagram
    actor U as Usuario
    participant N as Navegador
    participant C as LoginController
    participant S as Sesión (BD)

    U->>N: Clic en "Cerrar sesión" (CA-04.1)
    N->>C: POST /logout (+ token CSRF)
    C->>C: Auth::logout()
    C->>S: invalidate(): borra la sesión y crea otra con nuevo ID (CA-04.2)
    C->>C: regenerateToken() (nuevo CSRF)
    C-->>N: Redirige a /login (CA-04.3)

    Note over N: Botón Atrás o URL /dashboard
    N->>C: GET /dashboard
    Note over N,C: Las páginas privadas se envían con Cache-Control: no-store (middleware SinCache),<br/>así que el navegador debe pedirlas al servidor
    C-->>N: middleware auth → redirige a /login (CA-04.4)
```

## Secuencia HU-05 — Límite de intentos fallidos

```mermaid
sequenceDiagram
    actor U as Usuario
    participant N as Navegador
    participant F as LoginRequest
    participant RL as RateLimiter (caché)
    participant A as Auth

    U->>N: Envía correo y contraseña
    N->>F: authenticate()
    F->>RL: ¿existe login-bloqueo:hash(correo|IP)?
    alt Bloqueado (CA-05.2)
        F-->>N: "Superaste el número de intentos… Inténtalo de nuevo en N minutos" (CA-05.3)
    else No bloqueado
        F->>A: attempt(correo, contraseña)
        alt Credenciales correctas
            F->>RL: clear(login-fallos) (CA-05.5)
            F-->>N: Redirige a /dashboard
        else Credenciales incorrectas
            F->>RL: hit(login-fallos, ventana 15 min) (CA-05.1)
            alt Es el 5.º fallo
                F->>RL: hit(login-bloqueo, 15 min) (CA-05.2)
                F-->>N: Mensaje de bloqueo (CA-05.3)
            else Menos de 5 fallos
                F-->>N: "Las credenciales ingresadas no son válidas."
            end
        end
    end
    Note over RL: Pasados 15 min la clave de bloqueo expira y se puede volver a intentar (CA-05.4)
```
