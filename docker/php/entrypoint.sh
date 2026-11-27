#!/bin/sh
set -e

# Obraz jest wspoldzielony przez usluge php i cron (ten sam Dockerfile), ktore montuja ten sam
# katalog hosta (.:/app). Na czystym klonie (bez vendor/) kontenery musza zainstalowac zaleznosci
# samodzielnie (composer install nie jest robiony przy budowie obrazu), a przy starcie obu uslug
# naraz flock na pliku we wspoldzielonym katalogu pilnuje, zeby "composer install" nie wykonal sie
# dwa razy rownolegle na tym samym wolumenie (T-107).
if [ ! -f /app/vendor/autoload.php ]; then
    flock -x /app/.composer-install.lock -c '
        if [ ! -f /app/vendor/autoload.php ]; then
            echo "[entrypoint] vendor/autoload.php brak, instaluje composer install..."
            composer install --no-interaction --no-progress --working-dir=/app
        fi
    '
fi

exec "$@"
