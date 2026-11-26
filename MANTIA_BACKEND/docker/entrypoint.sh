#!/bin/sh
# Se ejecuta cada vez que arranca un contenedor del backend (backend, worker, scheduler).
set -e

# Generar la clave no necesita una clave previa
case "$*" in
    *key:generate*) exec "$@" ;;
esac

if [ -z "$APP_KEY" ]; then
    echo ""
    echo "ERROR: falta APP_KEY en MANTIA_BACKEND/.env.docker"
    echo "Genérala con:  docker compose run --rm --no-deps backend php artisan key:generate --show"
    echo "y pega el resultado en APP_KEY=..."
    echo ""
    exit 1
fi

# Carpetas que Laravel necesita (el volumen de storage/app puede venir vacío)
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Siempre lee la configuración nueva de .env.docker
php artisan config:clear >/dev/null 2>&1 || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan storage:link >/dev/null 2>&1 || true
    echo "Aplicando migraciones..."
    php artisan migrate --force
fi

exec "$@"
