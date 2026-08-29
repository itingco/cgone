# CGOne ERP V4 — Deployment

## Isi V4
- Filter Data View dipindah ke offcanvas agar halaman lebih rapi.
- Master Item memakai tabs; Price Level dan History di-load hanya saat tab dibuka.
- Customer memiliki Sales/AR History; Vendor memiliki Purchase/AP History.
- Dashboard per-user: widget, urutan, ukuran. Jika belum diset, halaman depan kosong dan tidak menjalankan query agregat dashboard.
- Profile kanan atas menampilkan Role, Department, Dashboard Layout, Change Password, Logout.
- Department ditambahkan ke User Configuration.
- Template COA distributor alat teknik 60 akun tersedia sebagai Seeder + CSV.
- Index ledger tambahan, quick-search prefix, lazy-load detail, dan export `chunkById` untuk mengurangi beban data besar.

## Deploy ke project existing
1. Backup source dan database.
2. Copy/overlay semua file dari paket ini ke root CGOne.
3. Jangan mengganti `.env` production.
4. Jalankan:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\DistributorTechnicalToolsCoaSeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Seeder COA otomatis berhenti bila tabel Chart of Accounts sudah memiliki data.

## Rollback database V4
```bash
php artisan migrate:rollback --step=1
```

Rollback migration menghapus dashboard preference, department user, dan index V4. File source tetap perlu dikembalikan dari backup bila rollback penuh diperlukan.
