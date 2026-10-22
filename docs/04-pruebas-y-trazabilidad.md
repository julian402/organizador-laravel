# 4. Casos de prueba y matriz de trazabilidad (Sprint 1)

Plan base: **TP-S01** — pruebas funcionales de caja negra (positivas, negativas, valores límite, partición de equivalencia y seguridad).

## Entorno de pruebas

| Requisito TP-S01 | Cómo se cumple |
|---|---|
| App funcionando local | `php artisan serve` → http://127.0.0.1:8000 |
| BD independiente para testing | PostgreSQL, base `organizador_test` (ver `src/phpunit.xml`) |
| Usuario registrado + correo no registrado | `ana@test.local` / `Abcd1234` y `nadie@test.local` |
| Correo de pruebas | `MAIL_MAILER=log` → `src/storage/logs/laravel.log` (para HU-03) |
| Repositorio Git | carpeta `Organizador/` |

## Casos de prueba

Ejecución manual: seguir los pasos en el navegador y tomar captura como evidencia.
Ejecución automática (apoyo): `php artisan test` desde `src/`.

### HU-01 — Registro (`/registro`)

| ID | CA | Tipo | Datos de entrada | Pasos | Resultado esperado | Prueba automática | Resultado real | Estado |
|---|---|---|---|---|---|---|---|---|
| CP-01 | CA-01.1, CA-01.6 | Positivo | Ana Pérez / ana@test.local / Abcd1234 / Abcd1234 | 1. Abrir /registro 2. Llenar campos 3. Registrarme | Usuario creado, redirige a login con mensaje de confirmación | `test_cp01_registro_con_datos_validos` | | |
| CP-02 | CA-01.2 | Negativo | Todos vacíos | 1. Abrir /registro 2. Registrarme sin llenar | Mensajes de obligatoriedad en los 4 campos; no se crea usuario | `test_cp02_campos_vacios` | | |
| CP-03 | CA-01.3 | Negativo | correo `ana@correo` | Registrar con ese correo | "El correo electrónico no tiene un formato válido." | `test_cp03_correo_con_formato_invalido` | | |
| CP-04 | CA-01.3 | Negativo | `ANA@test.local` (ya existe ana@test.local) | Registrar con ese correo | "Ya existe una cuenta registrada con este correo electrónico." | `test_cp04_correo_duplicado_sin_distinguir_mayusculas` | | |
| CP-05 | CA-01.4 | Límite | `Abc1234` (7) | Registrar con esa contraseña | "La contraseña debe tener al menos 8 caracteres." | `test_cp05_contrasena_de_7_caracteres_rechazada` | | |
| CP-06 | CA-01.4 | Límite | `Abcd1234` (8) | Registrar con esa contraseña | Contraseña aceptada | `test_cp06_contrasena_de_8_caracteres_aceptada` | | |
| CP-07 | CA-01.5 | Negativo | `Abcd1234` / `Abcd1235` | Registrar | "La contraseña y su confirmación no coinciden." | `test_cp07_confirmacion_diferente` | | |
| CP-08 | CA-01.7 | Seguridad | Usuario de CP-01 | En psql: `SELECT password FROM users WHERE email='ana@test.local';` | Valor tipo `$2y$12$...`, distinto al texto plano | `test_cp08_contrasena_almacenada_con_hash` | | |

### HU-02 — Inicio de sesión (`/login`)

Precondición: existe `ana@test.local` / `Abcd1234`.

| ID | CA | Tipo | Datos de entrada | Pasos | Resultado esperado | Prueba automática | Resultado real | Estado |
|---|---|---|---|---|---|---|---|---|
| CP-09 | CA-02.1, CA-02.2 | Positivo | ana@test.local / Abcd1234 | 1. Abrir /login 2. Ingresar datos 3. Entrar | Acceso al área privada (/dashboard) | `test_cp09_credenciales_correctas` | | |
| CP-10 | CA-02.3 | Negativo | ana@test.local / Abcd1235 | Igual a CP-09 | "Las credenciales ingresadas no son válidas." Sin acceso | `test_cp10_contrasena_incorrecta` | | |
| CP-11 | CA-02.3 | Negativo | nadie@test.local / Abcd1234 | Igual a CP-09 | Mismo mensaje genérico que CP-10 | `test_cp11_correo_no_registrado` | | |
| CP-12 | CA-02.4 | Negativo | Ambos vacíos | Entrar sin llenar | Mensajes de obligatoriedad; sin autenticación | `test_cp12_campos_vacios` | | |
| CP-13 | CA-02.6 | Seguridad | — | En ventana privada abrir /dashboard | Redirección a /login | `test_cp13_url_privada_sin_sesion` | | |
| CP-14 | CA-02.5 | Positivo | Sesión iniciada | Iniciar sesión y navegar a /dashboard | Se muestra "Hola, <nombre>" | `test_cp14_navegar_al_area_privada_con_sesion` | | |

### Seguridad (requisitos no funcionales, `tests/Feature/SeguridadTest.php`)

| ID | Tipo | Datos de entrada | Pasos | Resultado esperado | Prueba automática | Resultado real | Estado |
|---|---|---|---|---|---|---|---|
| CP-S1 | Seguridad | ana@test.local / Mala9999 ×5, luego Abcd1234 | 5 intentos fallidos y un 6.º con la clave correcta en menos de 1 min | "Demasiados intentos. Espera un minuto e inténtalo de nuevo." Sin acceso | `test_login_se_bloquea_tras_5_intentos_fallidos` | | |
| CP-S2 | Seguridad | 5 fallos con ana@, luego luis@ correcto | Igual, cambiando de correo | luis@ entra (el límite es por correo + IP) | `test_limite_de_login_es_por_correo` | | |
| CP-S3 | Seguridad | 6 registros seguidos desde la misma IP | Enviar /registro 6 veces en menos de 1 min | El 6.º se rechaza con el mensaje de límite | `test_registro_se_bloquea_tras_5_intentos` | | |
| CP-S4 | Seguridad | — | `curl -I http://127.0.0.1:8080/login` | Cabeceras X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, CSP; sin X-Powered-By ni versión de Apache | `test_respuestas_incluyen_cabeceras_de_seguridad` | | |

## Matriz de trazabilidad

| HU | Criterio | Implementación | Caso(s) |
|---|---|---|---|
| HU-01 | CA-01.1 Formulario nombre, correo, contraseña, confirmación | `auth/register.blade.php` | CP-01 |
| HU-01 | CA-01.2 Campos obligatorios | `RegisterRequest::rules()` `required` | CP-02 |
| HU-01 | CA-01.3 Correo válido y único sin distinguir mayúsculas | `RegisterRequest` (`email:rfc,strict`, `unique`, minúsculas) + mutator `User::email()` + índice `LOWER(email)` | CP-03, CP-04 |
| HU-01 | CA-01.4 Mín. 8, mayúscula, minúscula, número | `Password::min(8)->mixedCase()->numbers()` | CP-05, CP-06 |
| HU-01 | CA-01.5 Contraseña = confirmación | regla `confirmed` | CP-07 |
| HU-01 | CA-01.6 Crea usuario + mensaje | `RegisterController::store()` | CP-01 |
| HU-01 | CA-01.7 Hash seguro | cast `'password' => 'hashed'` (bcrypt) | CP-08 |
| HU-02 | CA-02.1 Formulario correo y contraseña | `auth/login.blade.php` | CP-09 |
| HU-02 | CA-02.2 Login con credenciales válidas | `LoginController::store()` → `Auth::attempt()` | CP-09 |
| HU-02 | CA-02.3 Mensaje genérico | mismo error para correo y contraseña | CP-10, CP-11 |
| HU-02 | CA-02.4 No permite campos vacíos | `LoginRequest::rules()` `required` | CP-12 |
| HU-02 | CA-02.5 Redirige a página privada | `redirect()->intended(route('dashboard'))` | CP-09, CP-14 |
| HU-02 | CA-02.6 Visitante no accede a privadas | middleware `auth` en `/dashboard` | CP-13 |

**Cobertura de criterios Sprint 1:** 13 / 13 criterios con al menos un caso (100 %).
