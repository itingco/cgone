CGOne Update V3 - Header Database Selector + Snapshot Reference
===============================================================

V3 changes:
- Database selector moved from the left sidebar to the application header.
- Business Unit selector remains in the sidebar as the active GL dimension.
- Database Manager gear is available beside the header database selector for authorized users.
- `as_ingco.snapshot` has been inspected and confirmed usable as a legacy ERP schema reference.
- See SNAPSHOT_SCHEMA_ANALYSIS.md for confirmed IC_Items, AR_Customers, AP_Suppliers and AC_Accounts fields.

CGOne ERP - Update V2
Database Manager + Profile + Collapsible Sidebar + ERP Master Tabs
Tanggal: 30 Agustus 2026

CARA INSTALL
============
1. Backup project dan database PostgreSQL Anda.
2. Extract ZIP ini.
3. Copy seluruh isi folder hasil extract ke ROOT project CGOne.
4. Pilih overwrite/timpa file yang sama.
5. JANGAN timpa file .env aktual Anda dengan .env.example.
6. Jalankan SATU perintah ini dari root project:

   php artisan optimize:clear && php artisan erp:migrate-databases --force

ENV ANDA
========
Untuk .env yang sebelumnya sudah saya perbaiki, konfigurasi berikut sudah sesuai:

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cgone_erp
DB_USERNAME=erp
DB_PASSWORD=...
ERP_DB_CONNECTION=pgsql
ERP_DATABASES="cgone_erp|CGOne ERP"
SESSION_DRIVER=file

Tidak perlu mengedit ERP_DATABASES lagi untuk setiap database baru.
Database yang ditambah melalui UI disimpan di:
storage/app/erp-databases.json

FITUR 1 - DATABASE MANAGER POSTGRESQL
=====================================
Lokasi:
Configuration > Database Manager
atau klik "Manage / Add Database" di selector database kiri atas.

Fungsi:
- Test Connection ke database PostgreSQL pada server yang sama.
- Register Existing Database.
- Create New Database.
- Create New Database otomatis menjalankan migration + seeder CGOne.
- User aktif, role, dan permission user ikut dibuat pada database baru berdasarkan code.
- Database baru otomatis masuk selector kiri atas.
- Unregister hanya melepas database dari selector. TIDAK melakukan DROP DATABASE.

Syarat Create Database:
User PostgreSQL dari DB_USERNAME harus memiliki privilege CREATEDB.
Jika tidak, halaman akan menampilkan error PostgreSQL dan database tidak dibuat.

Server/host/port/username/password database baru selalu mengikuti koneksi PostgreSQL utama dari .env.
Database Manager tidak menyimpan password baru.

FITUR 2 - PROFILE KANAN ATAS
=============================
Nama user kanan atas sekarang menjadi dropdown profile:
- Profile & Password
- My Documents
- Logout

Profile dapat mengubah Name. Email dibuat read-only karena dipakai sebagai identitas lintas database.
Perubahan Name disinkronkan ke seluruh database terdaftar yang memiliki user dengan email yang sama.
Change Password menggunakan current password + confirmation dan password baru juga disinkronkan ke seluruh database terdaftar.

My Documents menampilkan maksimal 200 dokumen posted terbaru milik user pada database aktif:
- Posted Shipment
- Posted Sales Invoice
- Posted Receipt
- Posted Purchase Invoice

FITUR 3 - SIDE PANEL SHOW / HIDE
================================
Tombol hamburger di topbar dapat show/hide sidebar.
Status disimpan di browser localStorage, sehingga pilihan tetap diingat saat pindah halaman.

FITUR 4 - MASTER DATA ERP DALAM TAB
===================================
Master Item, Customer, Supplier/Vendor, dan Chart of Accounts sekarang dikelompokkan dalam tab.

ITEM
- General
- Inventory & Procurement
- Accounting & Posting
- Description
- Control

Tambahan field:
Short Name, Barcode/EAN, Manufacturer Code, Model/Type, Country of Origin, HS Code,
Warranty, Weight/Length/Width/Height, Track Serial, Track Batch/Lot,
Sales Description, Purchase Description.

CUSTOMER
- General
- Address
- Commercial
- Accounting & Tax
- Control

Tambahan field:
Contact Person, Mobile, Fax, Website, Billing/Shipping Postal Code, Currency,
Salesperson Code, Tax Registered Name, PKP, Credit Hold, Notes.

SUPPLIER / VENDOR
- General
- Address
- Commercial
- Banking
- Accounting & Tax
- Control

Tambahan field:
Contact Person, Mobile, Fax, Website, Postal Code, Currency, Lead Time,
Minimum Order Value, Tax Registered Name, PKP, Purchase Hold, Notes.

CHART OF ACCOUNTS
- General
- Reporting
- Control

Tambahan field:
Account Subcategory, Report Group, Cash Flow Category, External/Legacy Code,
Control Account, Require Reconciliation, Notes.

CATATAN BACKUP ERP 123.BAK
==========================
File Drive 123.bak berukuran sekitar 1.216.471.040 bytes (±1,2 GB).
Konektor file pada sesi pengembangan memiliki limit 268.435.456 bytes (256 MB), sehingga backup tidak dapat di-download dan dibuka penuh dari sesi ini.

Karena itu field master pada update ini dibuat berdasarkan:
1. struktur CGOne repository saat ini; dan
2. struktur ERP yang memang sudah terlihat pada source/migration CGOne.

Field di atas TIDAK diklaim sebagai hasil ekstraksi penuh dari 123.bak.
Untuk mapping 1:1 terhadap database ERP lama, berikan schema-only SQL / daftar CREATE TABLE untuk tabel item/customer/supplier/account. File seperti itu jauh lebih kecil dan bisa dianalisis langsung.

ROLLBACK
========
Migration baru utama:
- 2026_08_30_000100_create_business_units_and_add_gl_dimension.php
- 2026_08_30_000200_expand_erp_master_fields.php

Rollback harus dilakukan per database. Backup terlebih dahulu sebelum rollback.
