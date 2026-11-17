# Despliegue en AWS

**En producción:** https://organizador-laravel.duckdns.org (región us-east-2, Ohio)

Arquitectura: **2 instancias EC2 con Ubuntu Server 26.04 LTS** (t3.micro).

| Máquina | Rol | Software | Puertos entrantes (Security Group) |
|---|---|---|---|
| `organizador-app` | Aplicación | Nginx + PHP 8.5-FPM + Laravel, Elastic IP | 22 (tu IP), 80 y 443 (0.0.0.0/0) |
| `organizador-db` | Base de datos | PostgreSQL 18 | 22 (tu IP), 5432 **solo desde `sg-app`** |

> Ninguna clave va al repositorio: la contraseña de la BD, el `APP_KEY` y la del correo solo existen en `/var/www/organizador/src/.env` del servidor (`root:www-data`, permisos `640`).

## 1. Red

Misma VPC para ambas. Crear 2 Security Groups (reglas **Inbound**; el Outbound se deja por defecto):

- `sg-app`: SSH 22 desde *My IP*, HTTP 80 y HTTPS 443 desde `0.0.0.0/0`.
- `sg-db`: SSH 22 desde *My IP*, PostgreSQL 5432 con origen `sg-app`.

## 2. Servidor de BD

```bash
scp -i ~/.ssh/<llave>.pem infra/aws/db-setup.sh ubuntu@<ip-publica-bd>:~
ssh -i ~/.ssh/<llave>.pem ubuntu@<ip-publica-bd>
sudo apt update && sudo apt upgrade -y
DB_PASSWORD=$(openssl rand -hex 24)      # guardarla en un gestor de contraseñas
sudo APP_PRIVATE_IP=<ip-privada-app> DB_PASSWORD="$DB_PASSWORD" bash db-setup.sh
sudo ss -tlnp | grep 5432                # debe estar escuchando
rm db-setup.sh && sudo reboot            # reboot si quedó un kernel pendiente
```

## 3. Servidor de la app

Con 1 GB de RAM se necesita swap para `composer install`:

```bash
sudo apt update && sudo apt upgrade -y && sudo reboot
sudo fallocate -l 1G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
apt-cache policy php-fpm                 # versión de PHP disponible (26.04 → 8.5)
```

Primera pasada (clona, instala y crea el `.env`):

```bash
sudo REPO_URL=https://github.com/julian402/organizador-laravel.git PHP_VERSION=8.5 bash app-setup.sh
```

Completar el `.env` sin que la clave quede en pantalla ni en el historial:

```bash
read -rs DBPASS                          # pegar la clave de la BD
sudo sed -i "s#^APP_URL=.*#APP_URL=http://<ip-publica-app>#; s#^DB_HOST=.*#DB_HOST=<ip-privada-bd>#; s#^DB_PASSWORD=.*#DB_PASSWORD=$DBPASS#" /var/www/organizador/src/.env
unset DBPASS
cd /var/www/organizador/src && sudo php artisan key:generate --force
sudo chown root:www-data .env && sudo chmod 640 .env
```

Segunda pasada (migra, cachea y publica en Nginx):

```bash
cd ~ && sudo REPO_URL=https://github.com/julian402/organizador-laravel.git PHP_VERSION=8.5 bash app-setup.sh
rm app-setup.sh
```

## 4. IP fija y dominio

1. **EC2 → Elastic IPs → Allocate** y luego **Associate** a la instancia de la app. La IP ya no cambia al apagar/encender.
2. En [duckdns.org](https://www.duckdns.org) crear el subdominio y apuntarlo a la Elastic IP.

## 5. HTTPS (Let's Encrypt)

```bash
D=organizador-laravel.duckdns.org
sudo sed -i "s/server_name _;/server_name $D;/" /etc/nginx/sites-available/organizador
sudo sed -i 's/^\s*#\?\s*server_tokens.*/\tserver_tokens off;/' /etc/nginx/nginx.conf
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d $D --redirect --agree-tos --register-unsafely-without-email
sudo certbot renew --dry-run             # comprueba la renovación automática

cd /var/www/organizador/src
sudo sed -i "s#^APP_URL=.*#APP_URL=https://$D#" .env
echo "SESSION_SECURE_COOKIE=true" | sudo tee -a .env
sudo php artisan config:cache
```

## 6. Correo (recuperación de contraseña)

Se usa **Gmail SMTP con contraseña de aplicación** (requiere verificación en 2 pasos: https://myaccount.google.com/apppasswords). Amazon SES no se usó porque exige registros DNS (DKIM) que DuckDNS no permite y empieza en *sandbox*.

```bash
cd /var/www/organizador/src
read -r CORREO                           # correo de Gmail
read -rs APPPASS                         # contraseña de aplicación (16 letras)
sudo sed -i "s#^MAIL_MAILER=.*#MAIL_MAILER=smtp#; s#^MAIL_FROM_ADDRESS=.*#MAIL_FROM_ADDRESS=\"$CORREO\"#" .env
printf 'MAIL_HOST=smtp.gmail.com\nMAIL_PORT=587\nMAIL_USERNAME=%s\nMAIL_PASSWORD=%s\n' "$CORREO" "${APPPASS// /}" | sudo tee -a .env >/dev/null
unset APPPASS CORREO
sudo php artisan config:cache
```

## 7. Validación

| Prueba | Esperado |
|---|---|
| `https://<dominio>/salud` | `{"app":"Organizador","mensaje":"Hola mundo","estado":"ok"}` |
| `http://<dominio>/login` | 301 a `https://` |
| `/.env`, `/.git/config` | 403 |
| `/dashboard` sin sesión | 302 al login |
| Puerto 5432 de la BD desde internet | cerrado |
| TLS 1.0 / 1.1 | rechazados (1.2 y 1.3 aceptados) |
| Cookies | `secure; httponly; samesite=lax` |
| Cabecera `Server` | `nginx` sin versión |
| Registro, login, bloqueo tras 5 fallos, logout + botón Atrás, recuperación por correo | funcionan |

## Actualizar la app

```bash
cd /var/www/organizador && sudo git pull
cd src && sudo composer install --no-dev --optimize-autoloader --no-interaction
sudo php artisan migrate --force && sudo php artisan optimize
```

## Costos

- **Stop** (no *Terminate*) las instancias cuando no se usen. La app conserva la Elastic IP; la BD cambia de IP pública, pero la app usa la privada.
- La Elastic IP se cobra (~3,6 USD/mes) aunque la instancia esté apagada. Al retirar el proyecto: *Disassociate* + *Release* la IP y *Terminate* las instancias.
- Recomendado: alerta en **Billing → Budgets**.

## Archivos

| Archivo | Uso |
|---|---|
| `db-setup.sh` | Instala PostgreSQL, crea usuario/BD, permite solo la IP privada de la app |
| `app-setup.sh` | Instala Nginx/PHP/Composer, clona el repo, migra, configura Nginx (`PHP_VERSION` configurable) |
| `nginx/organizador.conf` | Virtual host de Laravel (raíz en `src/public`, bloquea archivos ocultos) |
| `env.production.example` | Plantilla del `.env` de producción |

## Opción B: app en contenedor (en vez del paso 3)

En la EC2 de la app (la BD sigue igual, en su propia EC2 con `db-setup.sh`):

```bash
sudo apt-get update && sudo apt-get install -y docker.io git
sudo git clone <url-del-repo-git> /opt/organizador && cd /opt/organizador/src
sudo cp .env.docker.example .env.docker
sudo nano .env.docker        # APP_KEY, DB_HOST=<ip-privada-bd>, DB_PASSWORD, APP_URL, RUN_MIGRATIONS=true
sudo docker build -t organizador-app .
sudo docker run -d --name organizador --restart unless-stopped -p 80:80 --env-file .env.docker organizador-app
curl http://localhost/salud
```

Generar un APP_KEY: `sudo docker run --rm organizador-app php artisan key:generate --show`
