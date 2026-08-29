#!/usr/bin/env sh
set -eu

[ -f .env ] || cp .env.example .env
composer install --no-interaction
php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link || true
php artisan optimize:clear
printf '\nERP siap di http://localhost:8080\nLogin awal: admin@erp.local / ChangeMe123!\n'
