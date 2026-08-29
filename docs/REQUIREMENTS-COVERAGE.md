# Matriks Cakupan ERP Retail

Dokumen ini menjelaskan cakupan nyata source code pada paket awal. Status **Tersedia** berarti alur dan struktur utamanya sudah dibuat. Status **Fondasi** berarti skema atau service dasar tersedia, tetapi masih memerlukan penyempurnaan kebijakan, integrasi, atau UAT sebelum dipakai untuk operasional produksi.

## Teknologi dan arsitektur

| Kebutuhan | Status | Implementasi |
|---|---|---|
| Laravel 10 ke atas | Tersedia | Laravel 12, PHP 8.2 |
| PostgreSQL tunggal | Tersedia | PostgreSQL 16, migration, trigger, dan Row Level Security |
| Multi-group dan multi-company | Tersedia | Satu database dengan konteks perusahaan aktif dan RLS |
| Bahasa Indonesia | Tersedia | Navigasi, formulir, status, pesan, dan laporan utama |
| Responsif di mobile | Tersedia | App shell, tabel scroll, grid adaptif, dan formulir mobile |
| User friendly | Tersedia sebagai baseline | Dashboard, workbench proses, status dokumen, dan pesan validasi; tetap perlu UAT pengguna akhir |

## Integritas transaksi

| Kebutuhan | Status | Implementasi |
|---|---|---|
| Draft dapat diperbaiki | Tersedia | Perubahan dibatasi sebelum posting |
| Posted tidak dapat diedit atau dihapus | Tersedia | Guard aplikasi dan trigger PostgreSQL pada header serta detail |
| Koreksi melalui adjustment | Tersedia | Journal adjustment, journal reversal, inventory adjustment |
| Posting langsung ke accounting | Tersedia | Posting engine atomik menghasilkan ledger stok dan jurnal |
| Audit trail | Tersedia | Audit append-only, before/after, hash, user, IP, dan user agent |
| Period closing | Tersedia sebagai fondasi | Fiscal period dan pemeriksaan periode terbuka; proses closing formal perlu UAT Finance |

## Harga dan customer

| Kebutuhan | Status | Implementasi |
|---|---|---|
| Banyak price level per item | Tersedia | Kombinasi perusahaan, item, price level, UOM, dan tanggal efektif |
| Satu price level melekat pada customer | Tersedia | Resolver harga wajib memakai price level customer |
| Harga tidak tersedia memblokir transaksi | Tersedia | Tidak ada fallback harga default atau input manual |
| Perubahan harga wajib CEO | Tersedia | Approval CEO per baris dan larangan self-approval |
| Upload perubahan harga | Tersedia | CSV/XLSX, validasi per baris, status batch dan baris |
| Kolom Berlaku Mulai | Tersedia | Tanggal dan jam efektif |
| Tidak boleh backdate | Tersedia | Validasi domain dan import |
| Aktivasi otomatis | Tersedia | Scheduler mengaktifkan harga approved saat waktunya tiba |

## Purchasing dan Account Payable

| Kebutuhan | Status | Implementasi |
|---|---|---|
| PR → PO → Receipt → Invoice → Payment | Tersedia | Workbench dan relasi dokumen |
| Approval ≤ Rp5 juta Manager | Tersedia | Approval matrix |
| Approval > Rp5 juta Manager lalu CEO | Tersedia | Approval berurutan |
| Three-way matching dengan toleransi | Tersedia | Validasi qty, harga, dan nominal |
| Pembayaran penuh satu invoice | Tersedia | Outstanding wajib dilunasi penuh |
| Uang muka terikat satu PO | Tersedia | Supplier advance dan alokasi ke invoice |
| Retur pembelian/debit note | Fondasi | Koreksi dapat dilakukan melalui adjustment/reversal; dokumen retur khusus belum menjadi layar terpisah |

## Inventory dan costing

| Kebutuhan | Status | Implementasi |
|---|---|---|
| Multi-cabang dan multi-gudang | Tersedia | Master dan ledger per gudang |
| Multi-UOM dan multi-barcode | Tersedia | Master item UOM dan alias/barcode |
| Moving Average | Tersedia | Costing service berbasis saldo ledger |
| FIFO | Tersedia | Cost layer dan allocation |
| Standard Cost | Tersedia | Standard cost dan purchase variance |
| Kebijakan per perusahaan/kategori/item | Tersedia | Prioritas item, kategori, lalu default perusahaan |
| Batch, serial, dan kedaluwarsa | Fondasi | Struktur data tersedia; scan operasional dan traceability detail memerlukan fase lanjutan |
| Varian item | Fondasi | Struktur atribut/varian tersedia; configurator UI lengkap belum tersedia |
| Bundle, jasa, aset, non-stock, konsinyasi | Fondasi | Tipe item tersedia; lifecycle khusus setiap tipe belum seluruhnya dibuat |
| Stock opname | Tersedia sebagai koreksi | Inventory adjustment; worksheet stock opname lengkap perlu fase lanjutan |

## Integrasi penjualan

| Kebutuhan | Status | Implementasi |
|---|---|---|
| Tidak membuat ulang aplikasi penjualan | Tersedia | ERP menerima sinkronisasi dari sistem eksternal |
| Detail invoice dan item | Tersedia | Integration document dan lines |
| Anti-duplikasi | Tersedia | Source, external number, dan payload hash |
| Exception queue | Tersedia | Data invalid tidak menyentuh stok/accounting |
| Validasi customer price level dan harga efektif | Tersedia | Harga sumber harus cocok dengan harga ERP |
| Stok detail, jurnal harian ringkas | Tersedia | Inventory per invoice dan sales summary batch per hari |
| Rekonsiliasi antarsistem | Fondasi | Status, exception, dan batch tersedia; dashboard selisih end-to-end perlu disesuaikan dengan format sistem sumber |

## Accounting dan laporan

| Kebutuhan | Status | Implementasi |
|---|---|---|
| COA berbeda per perusahaan | Tersedia | COA dan posting profile per perusahaan |
| General Ledger | Tersedia | Hanya jurnal posted |
| Trial Balance | Tersedia | Filter periode |
| Profit & Loss | Tersedia | Berdasarkan kelompok akun |
| Balance Sheet | Tersedia | Berdasarkan kelompok akun |
| Inventory Valuation | Tersedia | Berdasarkan inventory ledger |
| AP Aging | Tersedia | Outstanding invoice supplier |
| Laporan sederhana buatan pengguna | Tersedia dengan batas aman | Sumber dan kolom memakai whitelist |
| Cash flow, budget vs actual, konsolidasi grup | Fondasi | Data model dapat diperluas; formula, eliminasi, dan workflow belum selesai |
| Pajak statutory Indonesia | Belum final | Perlu konfigurasi dan validasi sesuai kebijakan pajak perusahaan saat implementasi |
| Fixed asset lifecycle lengkap | Belum final | Tipe item tersedia, tetapi register aset, kapitalisasi, transfer, disposal, dan depreciation run perlu modul khusus |

## Verifikasi yang wajib dilakukan sebelum go-live

1. Jalankan Composer install, migration PostgreSQL, seeder, queue, dan scheduler pada environment target.
2. Jalankan seluruh test Laravel setelah dependensi tersedia.
3. Lakukan UAT per role: Purchasing, Warehouse, Finance, Accounting, Manager, CEO, Internal Control, dan Administrator.
4. Uji posting, reversal, closing period, concurrency, dan recovery menggunakan salinan data yang representatif.
5. Cocokkan posting profile dan Chart of Accounts setiap perusahaan.
6. Validasi pajak, format invoice, dan laporan statutory bersama Finance/Tax.
7. Lakukan penetration test, backup-restore drill, dan disaster recovery test.
8. Integrasikan sistem penjualan pada staging dan rekonsiliasi minimal satu periode penuh sebelum produksi.
