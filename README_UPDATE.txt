CGOne ERP — Cumulative Update R4 Visual Report Builder
Release: Version 31.08.2026
=====================================================

PAKET INI CUMULATIVE
--------------------
Paket ini mencakup update V1/V2/V3, Reporting R1 + hotfix Report Center,
Reporting R2 Sales/Purchase, Reporting R3 Inventory/Finance, dan Reporting R4
Visual Report Builder.

INSTALL
-------
1. Extract ZIP.
2. Copy semua isi ke root project CGOne.
3. Allow overwrite/replace.
4. Jalankan satu command:

php artisan optimize:clear && php artisan erp:migrate-databases --force --seed

Tidak perlu edit .env.

R4 - VISUAL REPORT BUILDER
--------------------------
Menu baru:
Reports -> Report Builder

Visual Builder memakai datasource dan field yang sudah di-whitelist aplikasi.
User biasa TIDAK mengisi nama tabel, JOIN, atau SQL.

Datasource awal:
- Sales Invoice Detail
- Sales Order Detail
- Customer Ledger
- Purchase Invoice Detail
- Purchase Order Detail
- Vendor Ledger
- Inventory Movement
- Stock Position
- General Ledger
- Item Master
- Customer Master
- Supplier Master
- Chart of Accounts
- Audit Activity

Fitur:
- pilih kolom;
- filter AND / OR;
- SUM / COUNT / AVG / MIN / MAX pada field yang diperbolehkan;
- grouping maksimal 4 level;
- sorting;
- calculated field aman: +, -, x, /;
- preview maksimal 500 row;
- report screen maksimal 5.000 row;
- summary cards;
- chart Column / Bar / Line / Pie / Donut;
- custom report default PRIVATE;
- Saved Views untuk nilai filter ketika report dijalankan;
- Share / Access per Role dan User;
- Clone Visual Report;
- Excel dan CSV;
- execution audit;
- version snapshot.

PERMISSION
----------
Report Center tetap menggunakan permission reports.center.

Report Builder menggunakan menu permission:
reports.builder

Administrator mendapat permission melalui seeder/reference data.
Role lain dapat diberi View/Create/Edit melalui konfigurasi menu-security yang sudah ada.

Setelah custom report dibuat, akses report diatur terpisah menggunakan:
- View
- Export
- Print
- Edit
- Share
- Clone
- Delete
- Manage

VISIBILITY
----------
PRIVATE : owner saja, kecuali diberi grant eksplisit.
SHARED  : user/role yang diberi report access.
COMPANY : seluruh active user mendapat View; hak lain tetap perlu grant.

BUSINESS UNIT
-------------
Business Unit tetap FIELD DI DALAM RECORD.

Visual Report Builder:
- dapat menampilkan BU sebagai kolom;
- dapat filter BU;
- dapat group/sort BU;
- TIDAK membaca BU aktif dari session sebagai hidden filter;
- jika filter BU dikosongkan, data seluruh BU yang sesuai filter lain ikut dibaca.

RUNTIME FILTER
--------------
Filter yang dibuat di Visual Builder akan muncul kembali sebagai filter ketika custom
report dibuka. User dapat mengganti nilai filter tanpa mengubah definisi report dan
dapat menyimpan kombinasi filter tersebut sebagai Saved View.

ADVANCED SQL — R5
-----------------
Advanced SQL Report sudah aktif untuk user yang memiliki menu permission reports.sql.
Default seeder/reference data memberikan akses penuh ke role ADMINISTRATOR.

Proteksi R5:
- hanya SELECT / WITH;
- DML / DDL / COPY / CALL / DO / multi-statement ditolak;
- SELECT FOR UPDATE/SHARE dan SELECT INTO ditolak;
- parameter memakai named binding (:parameter_name), bukan string concatenation;
- PostgreSQL menjalankan query di transaksi READ ONLY;
- statement timeout default 60 detik;
- preview maksimal 500 row;
- screen maksimal 5.000 row;
- synchronous export maksimal 25.000 row;
- sensitive parameter dimasking di execution log dan header Excel;
- execution tetap mengikuti database aktif di header;
- Business Unit hanya parameter/field eksplisit, tanpa hidden filter session.

Saved View tersedia untuk SQL parameter non-sensitive. Nilai parameter yang ditandai
sensitive tidak boleh disimpan ke Saved View.

VERIFIKASI PEMBUATAN PAKET
--------------------------
- PHP lint dijalankan pada seluruh file PHP patch.
- 14 datasource adapters terdaftar dan dibandingkan dengan config.
- Standalone validation harness untuk VisualReportValidator dijalankan.
- Standalone arithmetic harness untuk CalculationCompiler dijalankan.
- Standalone query-builder harness untuk VisualReportCompiler dijalankan.
- Static scan memastikan tidak ada hidden Business Unit session filter.
- Static scan memastikan Builder tidak menyediakan input table/JOIN/raw SQL.
- ZIP integrity diperiksa.
- Full php artisan test tidak dapat dijalankan di environment pembuat paket karena
  full repository + vendor Composer tidak tersedia.

Detail teknis:
REPORTING_R4_NOTES.md

REPORTING R5
------------
Detail teknis tambahan tersedia di REPORTING_R5_NOTES.md.

R6 REPORTING FINAL - 31.08.2026
--------------------------------
Adds PDF/Print, signed drill-down, centralized comparative periods, report version history,
6 management reports, report performance diagnostics, and reporting indexes.

R6 has one new Composer dependency: dompdf/dompdf ^3.1.
After copy/overwrite run exactly:

composer require dompdf/dompdf:^3.1 --no-interaction && php artisan optimize:clear && php artisan erp:migrate-databases --force --seed

Business Unit remains record data only. R6 does not add an implicit active-BU report filter.

R7 REPORTING STABILIZATION - 31.08.2026
----------------------------------------
R7 is a stabilization release. No new reporting business formula is introduced.

Fixes:
- CSV formula-injection hardening;
- Visual + Standard export-aware row budget (500 preview / 5,000 screen / 25,000 export);
- correct Visual/SQL version-history snapshots;
- sensitive SQL runtime parameters use POST and encrypted temporary session storage;
- sensitive SQL values are omitted from URL/export links/Saved Views;
- standalone acceptance checker and regression harness included.

No new migration or Composer dependency is introduced by R7.
If R6 has already been installed, after copy/overwrite run:

php artisan optimize:clear

Optional local reporting structure check:
php tools/reporting_acceptance_check.php

Details: REPORTING_R7_NOTES.md

H1 CORE HR + TRANSACTION TEMPLATE - 31.08.2026
------------------------------------------------
H1 starts the approved Human Capital roadmap and changes Business Unit behavior.

Changes:
- global/sidebar Business Unit selector removed;
- no active Business Unit session/context;
- BU is selected/stored per transaction;
- Configuration > Transaction Templates;
- source document defaults win, template fills only still-empty fields;
- Transaction Template values remain editable while the document is OPEN;
- Employee Master;
- Organization masters;
- effective-dated Employee Allocation History with overlap validation.

H1 does not backfill historical Business Unit data to MAIN.
Normal GL posting still derives accounts from Posting Groups + Posting Setup; Transaction
Template does not permit arbitrary GL-account defaults.

After copy/overwrite run:

php artisan optimize:clear && php artisan erp:migrate-databases --force --seed

Optional H1 structural check:
php tools/h1_acceptance_check.php

Details: H1_NOTES.md
