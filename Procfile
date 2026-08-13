web: php -S 0.0.0.0:${PORT} -t public
release: php artisan config:clear && php artisan route:clear && php artisan cache:clear && php artisan view:clear && php artisan migrate --force