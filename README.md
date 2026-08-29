# INGCO ERP Laravel Baseline

Baseline ERP untuk dijadikan fondasi migrasi/pengembangan dari ERP lokal existing.

## Scope versi ini

### Master Data
- Items
- Customers
- Vendors
- Chart of Accounts
- Warehouses
- Units of Measure (UOM)
- Price Levels

Setiap master menyediakan browse/search/filter, detail, create, edit, activate/deactivate, dan export CSV. Hard delete tidak diekspos di UI.

### Security & Configuration
- Users
- Roles
- Menu Security matrix
- Granular permissions: `view`, `create`, `edit`, `approve`, `post`, `reverse`, `export`
- Document numbering
- System settings

### Immutable Ledgers
- Item Ledger
- Customer Ledger
- Vendor Ledger
- General Ledger

Posted ledger menggunakan pola append-only. Aplikasi tidak menyediakan edit/delete untuk posted ledger. SQL Server protection script juga menolak direct `UPDATE` dan `DELETE`.

### Adjustment / Reversal
Jika posting salah, data asli tidak diubah. Menu Adjustment membuat entry pembalik:

- Item: `qty_in` dan `qty_out` dibalik.
- Customer/Vendor: `debit` dan `credit` dibalik.
- GL: seluruh journal line dibuat ulang dengan debit/credit terbalik.

Satu source entry hanya boleh direversal satu kali pada baseline ini.

### Activity Log
Mencatat login/logout, perubahan master, security/configuration, posting ledger, dan reversal. Before/after/meta disimpan sebagai JSON text agar tetap kompatibel dengan SQL Server.

## Requirement Server

- PHP 8.1+
- Composer 2.2+
- Microsoft SQL Server
- PHP extensions yang dibutuhkan Laravel dan SQL Server, terutama `pdo_sqlsrv` / `sqlsrv`
- Web server diarahkan ke folder `public/`

## Instalasi singkat

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setelah migration selesai, jalankan file berikut di SQL Server Management Studio:

```text
database/sql/sqlserver_ledger_protection.sql
```

Lalu verifikasi trigger dengan:

```text
database/sql/verify_sqlserver_setup.sql
```

## Development Administrator

Credential awal untuk development/first login:

```text
Email    : admin@erp.local
Password : ChangeMe123!
```

**Wajib ganti password setelah login pertama. Jangan gunakan credential ini sebagai password produksi permanen.**

## SQL Server `.env`

```dotenv
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=erp_baseline
DB_USERNAME=sa
DB_PASSWORD=your-password
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
```

Sesuaikan encryption settings dengan kebijakan server produksi.

## Mapping ke ERP Existing

Project ini sengaja memakai service boundary agar tabel/master dari backup ERP existing dapat dipetakan bertahap. Saat `.bak` sudah dapat diinspeksi, mapping dilakukan minimal untuk:

- Item master
- Customer master
- Vendor master
- COA
- Warehouse
- UOM
- Price level
- Tax/config
- Document numbering
- Existing ledger
- Stored procedures/views/functions terkait posting

Keputusan per object: `REUSE`, `MAP/ADAPT`, `REBUILD`, atau `DEPRECATE`.

## Catatan dependency

Folder `vendor/` tidak disertakan dalam release ZIP. Jalankan `composer install` pada server/deployment machine yang memiliki akses ke Packagist. Project memakai Laravel 10 dependency constraints pada `composer.json`.

## Testing

Source test berada di `tests/`. Setelah dependencies terpasang:

```bash
php artisan test
php artisan route:list
```

Test environment menggunakan SQLite in-memory untuk application-level behavior. SQL Server trigger perlu diverifikasi pada SQL Server menggunakan script deployment/verification yang disediakan.
