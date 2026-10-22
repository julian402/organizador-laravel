#!/usr/bin/env bash
# EC2 #2 — Servidor de base de datos (Ubuntu Server 24.04 LTS)
# Uso:  sudo APP_PRIVATE_IP=10.0.1.25 DB_PASSWORD='clave-segura' bash db-setup.sh
set -euo pipefail

: "${APP_PRIVATE_IP:?Defina APP_PRIVATE_IP (IP privada de la EC2 de la app)}"
: "${DB_PASSWORD:?Defina DB_PASSWORD}"
DB_NAME="${DB_NAME:-organizador}"
DB_USER="${DB_USER:-organizador}"

apt-get update
apt-get install -y postgresql postgresql-contrib

PG_VERSION=$(ls /etc/postgresql)
PG_CONF="/etc/postgresql/${PG_VERSION}/main"

# Escuchar en todas las interfaces; el acceso real lo limitan pg_hba y el Security Group.
sed -i "s/^#\?listen_addresses.*/listen_addresses = '*'/" "${PG_CONF}/postgresql.conf"

# Solo la máquina de la app puede conectarse.
grep -q "${APP_PRIVATE_IP}/32" "${PG_CONF}/pg_hba.conf" || \
    echo "host    ${DB_NAME}    ${DB_USER}    ${APP_PRIVATE_IP}/32    scram-sha-256" >> "${PG_CONF}/pg_hba.conf"

sudo -u postgres psql -v ON_ERROR_STOP=1 <<SQL
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '${DB_USER}') THEN
        CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASSWORD}';
    END IF;
END
\$\$;
SQL

sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname = '${DB_NAME}'" | grep -q 1 || \
    sudo -u postgres createdb -O "${DB_USER}" "${DB_NAME}"

systemctl enable postgresql
systemctl restart postgresql

echo "PostgreSQL ${PG_VERSION} listo. BD=${DB_NAME} USER=${DB_USER}, acepta conexiones desde ${APP_PRIVATE_IP}."
