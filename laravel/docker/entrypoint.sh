#!/bin/sh

echo "▶ Optimisations Laravel..."
php artisan config:cache  || echo "⚠ config:cache échoué, on continue"
php artisan route:cache   || echo "⚠ route:cache échoué, on continue"
php artisan view:cache    || echo "⚠ view:cache échoué, on continue"

echo "▶ Migration base de données..."
php artisan migrate --force || echo "⚠ migration échouée, on continue"

echo "▶ Démarrage Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
