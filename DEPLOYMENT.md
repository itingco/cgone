# Deployment Guide — INGCO ERP Laravel Baseline

## 1. Upload source

Extract source ke contoh path:

```bash
/var/www/ingco-erp
```

Pastikan document root Nginx/Apache mengarah ke:

```bash
/var/www/ingco-erp/public
```

## 2. Install dependency

```bash
cd /var/www/ingco-erp
composer install --no-dev --optimize-autoloader
```

## 3. Environment

```bash
cp .env.example .env
nano .env
php artisan key:generate
```

Set koneksi SQL Server dan `APP_URL` sesuai server.

## 4. Permission Linux

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 5. Database

Backup database target sebelum deployment production.

```bash
php artisan migrate --seed
```

Setelah itu, buka SSMS dan execute:

```text
database/sql/sqlserver_ledger_protection.sql
```

Verifikasi:

```text
database/sql/verify_sqlserver_setup.sql
```

Hasil yang diharapkan adalah 6 trigger aktif (`IsDisabled = 0`).

## 6. Laravel cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 7. First login

```text
Email    : admin@erp.local
Password : ChangeMe123!
```

Segera ubah password dari menu Configuration > Users.

## 8. Production verification

```bash
php artisan about
php artisan route:list
php artisan migrate:status
```

Cek:
- Login berhasil.
- Master data dapat dibrowse.
- Role tanpa permission menerima HTTP 403.
- Role Administrator melihat seluruh menu.
- Adjustment menghasilkan reversal, bukan update source.
- Direct `UPDATE` / `DELETE` ke ledger melalui SSMS ditolak trigger.
- Activity log bertambah setelah perubahan master/config/reversal.

## 9. Rollback deployment

Rollback aplikasi dilakukan melalui release/source sebelumnya. Jangan menghapus ledger posted untuk rollback bisnis. Kesalahan transaksi bisnis tetap dikoreksi menggunakan Adjustment / Reversal.
