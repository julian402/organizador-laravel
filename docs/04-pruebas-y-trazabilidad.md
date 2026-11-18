# 4. Casos de prueba y matriz de trazabilidad (Sprints 1 y 2)

Plan base: **TP-S01** — pruebas funcionales de caja negra (positivas, negativas, valores límite, partición de equivalencia y seguridad).

## Entorno de pruebas

| Requisito TP-S01 | Cómo se cumple |
|---|---|
| App funcionando local | `php artisan serve` → http://127.0.0.1:8000 |
| BD independiente para testing | PostgreSQL, base `organizador_test` (ver `src/phpunit.xml`) |
| Usuario registrado + correo no registrado | `ana@test.local` / `Abcd1234` y `nadie@test.local` |
| Correo de pruebas | Manual: `MAIL_MAILER=log` → el correo de HU-03 queda en `src/storage/logs/laravel.log`. Automático: `Notification::fake()` (no se envía nada real) |
| Control del tiempo | Las pruebas de vencimiento (30 min) y bloqueo (15 min) usan `$this->travel()` para adelantar el reloj sin esperar |
| Repositorio Git | carpeta `Organizador/` |

## Casos de prueba

Ejecución manual: seguir los pasos en el navegador y tomar captura como evidencia.
Ejecución automática: `C:\xampp\php\php.exe artisan test` desde `src/` (73 pruebas: 60 funcionales + 13 unitarias). Solo una historia: `artisan test --filter RecuperarContrasenaTest` (o `LogoutTest`, `LimiteIntentosTest`). Solo unitarias: `artisan test --testsuite Unit`.

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

### HU-03 — Recuperación de contraseña (`/recuperar`) — `tests/Feature/Auth/RecuperarContrasenaTest.php`

Precondición: existe `ana@test.local` / `Abcd1234`. En manual, el enlace se copia de `src/storage/logs/laravel.log`.

| ID | CA | Tipo | Datos de entrada | Pasos | Resultado esperado | Prueba automática | Resultado real | Estado |
|---|---|---|---|---|---|---|---|---|
| CP-15 | CA-03.1 | Positivo | — | 1. Abrir /login 2. Clic en "¿Olvidaste tu contraseña?" | Formulario con campo correo | `test_cp15_formulario_de_recuperacion_se_muestra` | | |
| CP-16 | CA-03.2, CA-03.3 | Positivo | `ANA@test.local` | Enviar el formulario | "Si el correo está registrado, recibirás un enlace…"; se envía el correo | `test_cp16_correo_registrado_recibe_enlace_y_mensaje_generico` | | |
| CP-17 | CA-03.2 | Negativo | `nadie@test.local` | Enviar el formulario | **Mismo** mensaje que CP-16; no se envía correo | `test_cp17_correo_no_registrado_recibe_el_mismo_mensaje` | | |
| CP-18 | CA-03.3 | Seguridad | Correo de CP-16 | En psql: `SELECT token FROM password_reset_tokens;` y comparar con el del enlace | En BD hay un hash `$2y$…`, distinto al token de 64 caracteres del enlace; el enlace usa APP_URL | `test_cp18_token_seguro_y_enlace_correcto`, `test_enlace_no_usa_el_host_de_la_peticion` | | |
| CP-19 | CA-03.3 | Positivo | Nueva clave `Nueva1234` ×2 | Abrir el enlace, escribir la nueva clave y guardar | Redirige a /login con "Tu contraseña fue actualizada…" | `test_cp19_restablecer_con_enlace_valido` | | |
| CP-20 | CA-03.4 | Límite | Enlace con 29 min | Usar el enlace a los 29 min | Contraseña cambiada | `test_cp20_enlace_valido_a_los_29_minutos` | | |
| CP-21 | CA-03.4 | Límite | Enlace con 31 min | Usar el enlace a los 31 min | "El enlace de recuperación no es válido o ya expiró…"; la clave no cambia | `test_cp21_enlace_expirado_a_los_31_minutos` | | |
| CP-22 | CA-03.5 | Negativo / límite | `Abc1234` (7), `abcd1234`, `ABCD1234`, `Abcdefgh`, confirmación distinta | Intentar guardar cada una | Mismos mensajes de HU-01; la clave no cambia | `test_cp22_nueva_contrasena_debe_cumplir_reglas_de_hu01` | | |
| CP-23 | CA-03.6 | Negativo | Enlace ya usado en CP-19 | Abrir de nuevo el mismo enlace | "El enlace… no es válido o ya expiró" | `test_cp23_enlace_ya_usado_se_rechaza` | | |
| CP-24 | CA-03.7 | Seguridad | `Abcd1234` (anterior) y `Nueva1234` | Iniciar sesión con cada una | La anterior es rechazada; la nueva entra | `test_cp24_contrasena_anterior_ya_no_permite_entrar` | | |
| CP-25 | CA-03.3 | Seguridad | Token alterado en la URL | Cambiar caracteres del token y guardar | "El enlace… no es válido o ya expiró" | `test_cp25_token_manipulado_se_rechaza` | | |
| CP-40 | CA-03.8 | Negativo | Enlace válido; nueva contraseña `Abcd1234` (igual a la actual) | 1. Abrir el enlace 2. Escribir la contraseña actual 3. Guardar 4. Escribir `Nueva1234` y guardar | Paso 3: "La nueva contraseña debe ser diferente a tu contraseña actual." y la clave no cambia. Paso 4: se guarda (el enlace no se gastó) | `test_cp40_nueva_contrasena_igual_a_la_actual_se_rechaza` | | |
| CP-41 | CA-03.8 | Límite | Nueva contraseña `aBCD1234` (solo cambian mayúsculas/minúsculas) | Guardar desde un enlace válido | Se acepta: es una contraseña distinta | `test_cp41_contrasena_que_solo_cambia_mayusculas_se_acepta` | | |
| CP-42 | CA-03.8 | Seguridad | Token inventado + la contraseña actual `Abcd1234` | Enviar el formulario | Solo "El enlace… no es válido o ya expiró": no revela si la contraseña era la actual | `test_cp42_sin_enlace_valido_no_se_revela_la_contrasena_actual` | | |

### HU-04 — Cierre de sesión — `tests/Feature/Auth/LogoutTest.php`

| ID | CA | Tipo | Datos de entrada | Pasos | Resultado esperado | Prueba automática | Resultado real | Estado |
|---|---|---|---|---|---|---|---|---|
| CP-26 | CA-04.1 | Positivo | Sesión iniciada | Abrir /dashboard | Botón "Cerrar sesión" visible | `test_cp26_opcion_de_cerrar_sesion_visible` | | |
| CP-27 | CA-04.2 | Seguridad | Sesión iniciada | 1. Anotar el ID de sesión (`SELECT id FROM sessions WHERE user_id IS NOT NULL;`) 2. Cerrar sesión 3. Repetir la consulta | El registro de la sesión ya no existe; la cookie anterior no da acceso | `test_cp27_cerrar_sesion_invalida_la_sesion_en_el_servidor` | | |
| CP-28 | CA-04.3 | Positivo | Sesión iniciada | Clic en "Cerrar sesión" | Redirige a /login con "Cerraste sesión correctamente." | `test_cp28_redirige_al_login` | | |
| CP-29 | CA-04.4 | Seguridad | — | Tras cerrar sesión, escribir /dashboard en la barra | Redirige a /login | `test_cp29_url_privada_despues_de_cerrar_sesion` | | |
| CP-30 | CA-04.4 | Seguridad | — | Tras cerrar sesión, pulsar el botón Atrás del navegador | No se muestra el panel: el navegador vuelve a pedirlo y redirige a /login (cabecera `Cache-Control: no-store`) | `test_cp30_paginas_privadas_no_se_guardan_en_cache` | | |
| CP-31 | — | Seguridad | — | Abrir `GET /logout` en la barra | 405 Método no permitido; la sesión sigue activa (solo se cierra con POST + CSRF) | `test_cp31_cerrar_sesion_por_get_no_permitido` | | |

### HU-05 — Límite de intentos fallidos — `tests/Feature/Auth/LimiteIntentosTest.php`

Precondición: existen `ana@test.local` y `luis@test.local` con clave `Abcd1234`. Clave incorrecta usada: `Mala9999`.

| ID | CA | Tipo | Datos de entrada | Pasos | Resultado esperado | Prueba automática | Resultado real | Estado |
|---|---|---|---|---|---|---|---|---|
| CP-32 | CA-05.1 | Límite | 4 fallos y luego la clave correcta | Iniciar sesión | Entra (4 fallos están dentro del límite) | `test_cp32_cuatro_fallos_aun_permiten_entrar` | | |
| CP-33 | CA-05.1, CA-05.2 | Límite | 5 fallos y luego la clave correcta | Iniciar sesión | El 5.º fallo muestra el mensaje de bloqueo; el 6.º intento es rechazado aunque la clave sea correcta | `test_cp33_quinto_fallo_bloquea_la_cuenta_en_esa_ip` | | |
| CP-34 | CA-05.3 | Seguridad | 5 fallos con `nadie@test.local` | Comparar el mensaje con CP-33 | Mismo mensaje comprensible ("Superaste el número de intentos… Inténtalo de nuevo en 15 minutos."); no revela si la cuenta existe | `test_cp34_mensaje_igual_para_cuenta_inexistente` | | |
| CP-35 | CA-05.2 | Límite | Bloqueo + 14 min | Intentar con la clave correcta | Sigue bloqueado ("…en 1 minuto.") | `test_cp35_sigue_bloqueado_a_los_14_minutos` | | |
| CP-36 | CA-05.4 | Positivo | Bloqueo + 15 min | Intentar con la clave correcta | Entra | `test_cp36_despues_del_bloqueo_se_puede_entrar` | | |
| CP-37 | CA-05.5 | Positivo | 4 fallos → éxito → salir → 4 fallos → clave correcta | Iniciar sesión | Entra: el éxito reinició el contador | `test_cp37_login_exitoso_reinicia_el_contador` | | |
| CP-38 | CA-05.1 | Partición | Bloqueo de ana desde IP A; luis desde IP A; ana desde IP B | Iniciar sesión en cada caso | Solo se bloquea la combinación ana + IP A | `test_cp38_el_bloqueo_es_por_cuenta_e_ip` | | |
| CP-39 | CA-05.1 | Límite | 3 fallos, esperar 16 min, 3 fallos | Iniciar sesión con la clave correcta | Entra: los fallos fuera de la ventana de 15 min no se suman | `test_cp39_ventana_de_15_minutos` | | |

> Prueba manual de CP-35/CP-36: esperar el tiempo real o, en local, vaciar la caché con `php artisan cache:clear` para simular el fin del bloqueo.

### Seguridad adicional (`tests/Feature/SeguridadTest.php` y pruebas extra)

| ID | Tipo | Resultado esperado | Prueba automática |
|---|---|---|---|
| CP-S1 | Seguridad | Una IP no puede probar más de 20 correos por minuto en /login | `test_login_limita_intentos_por_ip` |
| CP-S2 | Seguridad | Una IP no puede pedir más de 5 enlaces de recuperación por minuto | `test_recuperar_limita_solicitudes_por_ip` |
| CP-S3 | Seguridad | El 6.º registro seguido desde la misma IP se rechaza | `test_registro_se_bloquea_tras_5_intentos` |
| CP-S4 | Seguridad | Cabeceras X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, CSP; sin X-Powered-By | `test_respuestas_incluyen_cabeceras_de_seguridad` |
| CP-S5 | Seguridad | Pedir un enlace nuevo invalida el anterior | `test_nuevo_enlace_invalida_el_anterior` |
| CP-S6 | Seguridad | Restablecer la contraseña renueva el `remember_token` | `test_restablecer_renueva_remember_token` |
| CP-S7 | Seguridad | Intentos con campos vacíos no cuentan para el bloqueo; el correo en mayúsculas sí cuenta para la misma cuenta | `test_campos_vacios_no_cuentan_como_fallo`, `test_mayusculas_no_evitan_el_limite` |

## Pruebas unitarias (Sprint 2)

Prueban una función o clase **aislada** (sin navegador ni rutas HTTP), en `src/tests/Unit/`. Ejecución: `C:\xampp\php\php.exe artisan test --testsuite Unit`.

| ID | HU / CA | Función validada | Datos de prueba | Resultado esperado | Resultado real | Estado |
|---|---|---|---|---|---|---|
| UT-01 | HU-03 / CA-03.8 | Regla `DistintaDeLaActual::validate()` | Contraseña actual `Abcd1234`; nueva `Abcd1234` | Falla con "La nueva contraseña debe ser diferente a tu contraseña actual." | Falla con el mensaje esperado | Aprobada |
| UT-02 | HU-03 / CA-03.8 | Regla `DistintaDeLaActual::validate()` | Actual `Abcd1234`; nuevas `Nueva1234`, `aBCD1234`, `Abcd12345`, ` Abcd1234` | Las 4 pasan (son distintas) | Las 4 pasan | Aprobada |
| UT-03 | HU-01, HU-03 / CA-01.4, CA-01.5, CA-03.5 | Trait `ValidaContrasena::reglasContrasena()` | `Abc1234`, `abcd1234`, `ABCD1234`, `Abcdefgh`; `Abcd1234` con confirmación `Abcd1235` | Cada una falla con su mensaje (8 caracteres / mayúscula y minúscula / número / no coinciden) | Cada una falla con el mensaje esperado | Aprobada |
| UT-04 | HU-01, HU-03 / CA-01.4, CA-03.5 | Trait `ValidaContrasena::reglasContrasena()` | `Abcd1234` (8, límite), `Nueva1234`, `aBCD1234`, `Clave-Segura-2026` | Todas pasan | Todas pasan | Aprobada |
| UT-05 | HU-03 / CA-03.4 | `Password::broker()->tokenExists()` + `config('auth.passwords.users.expire')` | Token recién creado; reloj adelantado 29 min | Vigencia configurada = 30; el token sigue válido | 30; token válido | Aprobada |
| UT-06 | HU-03 / CA-03.4, CA-03.3 | `Password::broker()->tokenExists()` | Token alterado (`token+x`); token original con reloj adelantado 31 min | Ambos inválidos | Ambos inválidos | Aprobada |
| UT-07 | HU-05 / CA-05.1, CA-05.2, CA-05.3 | `LoginRequest::authenticate()` | `ana@test.local` + `Incorrecta1` × 5 desde IP 10.0.0.1 | Fallos 1–4: "Las credenciales ingresadas no son válidas."; fallo 5: mensaje de bloqueo "…Inténtalo de nuevo en 15 minutos." | Igual al esperado | Aprobada |
| UT-08 | HU-05 / CA-05.1, CA-05.2, CA-05.4 | `LoginRequest::authenticate()` | Cuenta bloqueada; clave correcta `Abcd1234` desde la misma IP, desde IP 10.0.0.2, a los 14 min y a los 16 min | Misma IP: bloqueada; otra IP: entra; 14 min: "…en 1 minuto."; 16 min: entra | Igual al esperado | Aprobada |
| UT-09 | HU-04 / CA-04.4 | Middleware `SinCache::handle()` | Respuesta con contenido `privado` | `Cache-Control` con no-store, no-cache, must-revalidate, max-age=0, private; `Pragma: no-cache`; `Expires: 0`; contenido intacto | Igual al esperado | Aprobada |
| UT-10 | HU-03 / CA-03.1 | `ForgotPasswordRequest` (`prepareForValidation` + `rules`) | `"  Ana@Test.LOCAL "`; inválidos: vacío, `ana`, `ana@`, `ana @test.local`, correo de 261 caracteres | El válido queda `ana@test.local`; los 5 inválidos se rechazan | Igual al esperado | Aprobada |
| UT-S1 | HU-01 / CA-01.3 | `RegisterRequest` (regla `unique`) | Correo nuevo / correo ya registrado | Acepta / rechaza | Igual al esperado | Aprobada |

> UT-S1 corresponde a `tests/Unit/UniqueEmailTest.php` (Sprint 1, 2 pruebas). La suite unitaria tiene además el ejemplo de Laravel: 10 + 2 + 1 = 13.

### Verificación de las pruebas automáticas

Para comprobar que las pruebas detectan errores reales, se alteró el código a propósito y se verificó que fallaran:

| Cambio introducido | Prueba que lo detecta |
|---|---|
| Token con vigencia de 60 min | CP-21 |
| No reiniciar el contador tras un login exitoso | CP-37 |
| Quitar `Cache-Control: no-store` de las páginas privadas | CP-30 |
| Responder distinto si el correo no existe | CP-17 |
| Bloqueo de 10 min en vez de 15 | CP-33 a CP-36 |
| No invalidar la sesión al cerrar sesión | CP-27 |
| No validar que la nueva contraseña sea distinta de la actual | CP-40 |
| Regla `DistintaDeLaActual` que nunca falla | UT-01, CP-40 |
| Regla `DistintaDeLaActual` que siempre falla | UT-02, CP-41 |
| Límite de intentos en 4 o en 6 en vez de 5 | UT-07, UT-08 |
| Token con vigencia de 60 min | UT-05, UT-06 |

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

| HU-03 | CA-03.1 Formulario para solicitar recuperación | `ForgotPasswordController::create()`, `auth/forgot-password.blade.php`, enlace en login | CP-15 |
| HU-03 | CA-03.2 Mensaje genérico exista o no el correo | `ForgotPasswordController::store()` ignora el resultado de `sendResetLink` | CP-16, CP-17 |
| HU-03 | CA-03.3 Token seguro de un solo uso enviado al correo | Password broker (token de 64 caracteres, guardado con hash) + `RestablecerContrasena` (enlace con APP_URL) | CP-16, CP-18, CP-19, CP-25 |
| HU-03 | CA-03.4 Expira a los 30 minutos | `config/auth.php` → `passwords.users.expire = 30` | CP-20, CP-21 |
| HU-03 | CA-03.5 Reglas de contraseña de HU-01 | trait `ValidaContrasena` (compartido con `RegisterRequest`) | CP-22 |
| HU-03 | CA-03.6 Token invalidado tras el cambio | `Password::reset()` borra el token | CP-23 |
| HU-03 | CA-03.7 La contraseña anterior deja de servir | `ResetPasswordController::store()` guarda el nuevo hash | CP-24 |
| HU-03 | CA-03.8 La nueva contraseña no puede ser igual a la actual *(agregado por solicitud de la docente)* | regla `App\Rules\DistintaDeLaActual`, evaluada en `ResetPasswordController::store()` después de validar el token | CP-40, CP-41, CP-42, UT-01, UT-02 |
| HU-04 | CA-04.1 Opción para cerrar sesión | botón en `layouts/app.blade.php` (POST + CSRF) | CP-26 |
| HU-04 | CA-04.2 Invalida la sesión en el servidor | `LoginController::destroy()` → `invalidate()` + `regenerateToken()` | CP-27 |
| HU-04 | CA-04.3 Redirige al login | `redirect()->route('login')` | CP-28 |
| HU-04 | CA-04.4 Sin acceso por URL ni botón Atrás | middleware `auth` + middleware `SinCache` (`Cache-Control: no-store`) | CP-29, CP-30 |
| HU-05 | CA-05.1 Máx. 5 fallos por cuenta + IP en 15 min | `LoginRequest::registrarIntentoFallido()` (clave = hash de correo + IP, ventana 15 min) | CP-32, CP-33, CP-38, CP-39 |
| HU-05 | CA-05.2 Bloqueo de 15 minutos | `LoginRequest::asegurarQueNoEstaBloqueado()` (clave `login-bloqueo`, 15 min) | CP-33, CP-35 |
| HU-05 | CA-05.3 Mensaje comprensible sin datos sensibles | `LoginRequest::lanzarBloqueo()` (igual exista o no la cuenta) | CP-34 |
| HU-05 | CA-05.4 Se puede reintentar tras el bloqueo | la clave de bloqueo expira a los 15 min | CP-36 |
| HU-05 | CA-05.5 Login exitoso reinicia el contador | `RateLimiter::clear()` en `LoginRequest::authenticate()` | CP-37 |

**Cobertura de criterios:** Sprint 1: 13 / 13. Sprint 2: 17 / 17. Total: **30 / 30 criterios con al menos un caso (100 %)**.

**Pruebas automáticas:** 73 pruebas, 73 aprobadas (`php artisan test`): 60 funcionales (`tests/Feature`) y 13 unitarias (`tests/Unit`).
