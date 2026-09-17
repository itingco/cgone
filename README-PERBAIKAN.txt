CGOne ERP - Paket Perbaikan 2026-09-17 V2
=========================================

Cara pakai
---------
1. Backup project dan database terlebih dahulu.
2. Extract ZIP ini.
3. Copy seluruh isi folder `cgone-fixes` ke root project cgone, pertahankan struktur folder.
4. Izinkan overwrite file yang sama.
5. Jalankan:

   php artisan optimize:clear
   php artisan test
   php artisan route:list

6. Untuk contract check tambahan:

   php tests/Contracts/verify_document_source_integrity.php

Perbaikan yang termasuk
-----------------------
A. Ledger
- Ledger tidak lagi menampilkan Action / Reverse.
- Item Ledger memakai satu kolom Qty dengan nilai signed: qty_in - qty_out.
- Ada summary Qty berdasarkan filter aktif.
- Document Type dan Source ditampilkan agar asal ledger lebih jelas.
- Posted ledger tetap read-only.

B. Docker / SQL Server
- Dockerfile diselaraskan dengan DB_CONNECTION=sqlsrv.
- Menambahkan Microsoft ODBC Driver 18, sqlsrv, dan pdo_sqlsrv.
- Dockerfile lama memasang PostgreSQL driver tetapi konfigurasi project memakai SQL Server.

C. Purchase source-document integrity
- Purchase Order line dari Purchase Request harus berasal dari PR yang dipilih dan item yang sama.
- Receipt line harus berasal dari Purchase Order yang dipilih dan item yang sama.
- Purchase Invoice dari Posted Receipt harus cocok Item + Vendor + Purchase Order.
- Posted Receipt yang sudah UNDO tidak bisa dipakai lagi membuat Purchase Invoice.
- ID source line sekarang divalidasi exists pada tabel asal.

D. Sales source-document integrity
- Sales Order line dari Sales Request harus berasal dari SR yang dipilih dan item yang sama.
- Shipment source line tetap dicek terhadap Sales Order + Item.
- Sales Invoice dari Posted Shipment harus cocok Item + Customer + Sales Order.
- ID source line sekarang divalidasi exists pada tabel asal.

E. Concurrency / row locking
- update() Sales dan Purchase sekarang mengambil lockForUpdate DI DALAM DB::transaction.
- release() dan reopen() juga memakai row lock di dalam transaksi.
- Sebelumnya update() mengambil lockForUpdate sebelum transaksi dimulai sehingga lock tidak melindungi seluruh operasi update.

Temuan arsitektur yang TIDAK diubah
-----------------------------------
Project memiliki dua kelompok posting code:
- App\Services\Posting -> dipakai route/controller HTTP saat ini dan menulis ke item_ledgers/customer_ledgers/vendor_ledgers.
- App\Application\Posting -> engine lain yang memakai tabel inventory_transactions/inventory_ledger.

Keduanya tidak digabung dalam paket ini. Menggabungkan tanpa keputusan migrasi dapat menyebabkan double posting atau histori ledger terpecah.

Manual test prioritas
--------------------
1. PR A dan PR B masing-masing punya item berbeda. Buat PO dari PR A lalu manipulasi request source line agar menunjuk line PR B. Harus ditolak.
2. PO A dan PO B. Buat Receipt untuk PO A lalu kirim source_purchase_order_line_id dari PO B. Harus ditolak.
3. Posted Receipt Vendor A. Coba buat Purchase Invoice Vendor B dengan source line receipt Vendor A. Harus ditolak.
4. UNDO Posted Receipt lalu coba Create Purchase Invoice dari posted_id tersebut. Harus ditolak.
5. SR/SO/Shipment lakukan skenario silang yang sama. Harus ditolak.
6. Buka dua session/browser pada dokumen OPEN yang sama. Release di session 1, lalu submit Edit dari session 2. Update tidak boleh menimpa status RELEASED.
7. Buka Item Ledger, pastikan Qty Out tampil negatif, Qty In positif, dan summary mengikuti filter.

Catatan verifikasi paket
------------------------
- Syntax PHP file perubahan telah dicek dengan `php -l`.
- Contract checker source-document integrity telah dijalankan dan lulus.
- Full `php artisan test` harus dijalankan di local project setelah paket dioverwrite karena environment pembuat paket ini tidak memiliki dependency Composer/vendor project lengkap.

============================================================
V3 - TEST ENVIRONMENT ISOLATION (17 Sep 2026)
============================================================
Temuan dari hasil `php artisan test` lokal:
- 66 test PASS, 57 test FAIL.
- Failure pertama menunjukkan driver `pgsql` mencoba membuka database `:memory:`.
- Setelah failure pertama, banyak test berikutnya gagal dengan
  `PDOException: There is already an active transaction` pada SQLite.

Root cause yang ditangani:
1. phpunit.xml sebelumnya meminta SQLite, tetapi tidak memaksa nilai env.
2. Local/server environment atau DATABASE_URL dapat mengalahkan konfigurasi test.
3. Config cache hasil `php artisan optimize` / `config:cache` dapat membawa
   koneksi production ke test process.
4. Akibat koneksi test campur pgsql + sqlite, RefreshDatabase meninggalkan
   transaction state yang membuat failure berikutnya menjadi efek domino.

File V3:
- phpunit.xml
- tests/CreatesApplication.php
- tests/Feature/System/TestingEnvironmentIsolationTest.php
- tests/Contracts/verify_testing_environment.php

Setelah overwrite file V3 ke project lokal, jalankan:

  php artisan optimize:clear
  composer dump-autoload
  php tests/Contracts/verify_testing_environment.php
  php artisan test --filter=TestingEnvironmentIsolationTest
  php artisan test

CATATAN:
Jangan jalankan test suite terhadap database production. V3 secara eksplisit
mengunci automated tests ke SQLite in-memory supaya aman dan reproducible.

Jika full suite setelah V3 masih memiliki failure, kirim output terbaru.
Failure yang tersisa setelah problem database isolation hilang akan menjadi
bug aplikasi/test yang sebenarnya dan dapat diperbaiki satu per satu.

============================================================
V4 - SQLITE REFRESHDATABASE TRANSACTION GUARD
============================================================
Hasil test V3 membuktikan DB test sudah benar-benar SQLite, tetapi test suite
masih mengalami:

    PDOException: There is already an active transaction

Root symptom-nya adalah cached PDO SQLite :memory: milik RefreshDatabase masih
berada di dalam transaction ketika dipakai kembali oleh test berikutnya.

V4 menambahkan tests/TestCase.php dengan hook beforeRefreshingDatabase(). Hook
ini melakukan rollback HANYA jika:
- APP sedang environment testing
- default DB adalah sqlite
- database adalah :memory:
- cached PDO memang masih inTransaction()

Tidak ada perubahan pada koneksi/runtime database production.

Setelah overwrite file V4, jalankan:

    php artisan optimize:clear
    composer dump-autoload
    php tests/Contracts/verify_testing_environment.php
    php tests/Contracts/verify_refresh_database_transaction_guard.php
    php artisan test --filter=TestingEnvironmentIsolationTest
    php artisan test

Jika full test masih memiliki failure, kirim output full `php artisan test` lagi.

V5 - RefreshDatabase lifecycle compatibility
--------------------------------------------
- Removed the custom beforeRefreshingDatabase() hook from Tests\\TestCase.
  Laravel's RefreshDatabase trait owns that hook and its signature is untyped;
  declaring a typed override in the base test case causes a PHP fatal error.
- The stale SQLite :memory: PDO cleanup now runs in Tests\\TestCase::setUp()
  before parent::setUp(), i.e. before Laravel invokes RefreshDatabase.
- This change is test-only and does not touch production database code.

Run after applying V5:
  php artisan optimize:clear
  composer dump-autoload
  php tests/Contracts/verify_refresh_database_transaction_guard.php
  php artisan test --filter=TestingEnvironmentIsolationTest
  php artisan test

The PHPUnit doc-comment metadata warning is non-fatal. It can be migrated to
PHPUnit attributes separately once the exact local test file is available.
