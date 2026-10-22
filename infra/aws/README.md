# Despliegue en AWS (pendiente — aún NO se monta)

Arquitectura: **2 instancias EC2 con Ubuntu Server 24.04 LTS** (nada de Windows).

| Máquina | Rol | Software | Puertos entrantes (Security Group) |
|---|---|---|---|
| `organizador-app` | Aplicación | Nginx + PHP 8.3-FPM + Laravel | 22 (tu IP), 80/443 (0.0.0.0/0) |
| `organizador-db` | Base de datos | PostgreSQL (motor por defecto de Ubuntu) | 22 (tu IP), 5432 **solo desde el SG de la app** |

## Pasos

1. **Red:** misma VPC/subred para ambas. Crear 2 Security Groups (`sg-app`, `sg-db`). En `sg-db` regla 5432 con origen `sg-app`.
2. **EC2 BD:** lanzar Ubuntu 24.04 (t2.micro / t3.micro), copiar `db-setup.sh` y ejecutar:
   ```bash
   sudo APP_PRIVATE_IP=<ip-privada-app> DB_PASSWORD='<clave>' bash db-setup.sh
   ```
3. **EC2 App:** lanzar Ubuntu 24.04, ejecutar:
   ```bash
   sudo REPO_URL=<url-del-repo-git> bash app-setup.sh   # 1ª vez crea .env y se detiene
   sudo nano /var/www/organizador/src/.env               # DB_HOST=<ip-privada-bd>, DB_PASSWORD, APP_URL
   sudo REPO_URL=<url-del-repo-git> bash app-setup.sh   # 2ª vez migra y publica en Nginx
   ```
4. **Verificar ("hola mundo"):** `http://<ip-publica-app>/salud` → `{"mensaje":"Hola mundo","estado":"ok"}`
5. Probar conexión a BD desde la app: `psql -h <ip-privada-bd> -U organizador -d organizador`

## Archivos

| Archivo | Uso |
|---|---|
| `db-setup.sh` | Instala PostgreSQL, crea usuario/BD, permite solo la IP de la app |
| `app-setup.sh` | Instala Nginx/PHP/Composer, clona el repo, migra, configura Nginx |
| `nginx/organizador.conf` | Virtual host de Laravel (raíz en `src/public`) |
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
