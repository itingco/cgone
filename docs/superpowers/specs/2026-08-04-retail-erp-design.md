# Desain ERP Retail Multi-Company

## Tujuan
Membangun ERP web berbahasa Indonesia untuk grup perusahaan retail menggunakan Laravel 12, PHP 8.2, dan PostgreSQL. Sistem harus mudah digunakan, responsif di perangkat mobile, memiliki audit trail lengkap, dan menjadikan transaksi yang sudah diposting sebagai data immutable.

## Keputusan Utama
- Satu database PostgreSQL untuk seluruh grup.
- Isolasi data memakai `group_id`, `company_id`, `branch_id`, dan Row Level Security PostgreSQL.
- Setiap perusahaan bebas memiliki Chart of Accounts sendiri.
- Dokumen memakai alur `Draft -> Menunggu Approval -> Approved -> Posted`.
- Dokumen posted tidak dapat diedit atau dihapus. Koreksi dilakukan dengan adjustment, reversal, retur, debit note, atau credit note.
- Semua posting operasional membuat jurnal accounting dalam transaksi database yang sama.
- Approval transaksi: sampai Rp5.000.000 oleh Manager; di atas Rp5.000.000 oleh Manager lalu CEO.
- Semua perubahan harga jual hanya disetujui CEO, per baris item.
- Harga baru memiliki kolom `berlaku_mulai`, tidak boleh backdate, dan baru aktif setelah approval serta waktu efektif tercapai.
- Satu customer memiliki satu price level aktif. Item tanpa harga pada price level customer diblokir.
- Costing dapat dipilih per perusahaan atau kategori: Moving Average (default), FIFO, atau Standard Cost.
- Purchasing: Purchase Request -> Purchase Order -> Goods Receipt -> Supplier Invoice -> Full Payment.
- Three-way matching memiliki toleransi harga/kuantitas yang dapat dikonfigurasi.
- Uang muka supplier terkait tepat satu Purchase Order.
- Penjualan berasal dari sistem eksternal: detail invoice/item disimpan, jurnal accounting diringkas harian.

## Arsitektur
Modular monolith dengan bounded context: Organization, Identity, Catalog, Pricing, Purchasing, Inventory, Accounting, Approval, Integration, Reporting, dan Audit. Controller hanya menangani HTTP; aturan bisnis berada pada domain service. Posting menggunakan handler per jenis dokumen dan seluruh efek stok/jurnal berjalan dalam satu transaksi PostgreSQL.

## Keamanan dan Audit
- Laravel policies dan role/permission membatasi aksi per perusahaan dan cabang.
- PostgreSQL RLS menjadi pertahanan kedua.
- Audit log append-only mencatat actor, aksi, before/after hash, waktu, IP, dan user agent.
- Trigger PostgreSQL menolak UPDATE/DELETE terhadap dokumen berstatus posted.
- Idempotency key mencegah sinkronisasi penjualan ganda.

## Modul Fase Fondasi
1. Organisasi, user, role, company access.
2. Master item, UOM, barcode, customer, supplier, price level.
3. Price change upload, validasi, approval CEO per baris, aktivasi terjadwal.
4. Purchase Request, Purchase Order, penerimaan, invoice supplier, pembayaran penuh, uang muka.
5. Inventory ledger dan costing policy.
6. Chart of Accounts, fiscal period, journal entry, posting profile.
7. Sales integration staging, exception queue, dan summary posting.
8. Dashboard, report builder sederhana, laporan accounting dasar dan advanced-ready.

## Laporan Accounting
- Trial Balance
- General Ledger
- Balance Sheet
- Profit & Loss
- Cash Flow indirect
- AP Aging
- Inventory Valuation
- Stock Card
- Purchase Analysis
- Budget vs Actual (struktur disiapkan)
- Konsolidasi grup melalui mapping akun sebagai fase lanjutan

## UI
App shell responsif dengan sidebar desktop dan drawer mobile. Halaman memakai bahasa Indonesia, tabel tetap terbaca pada layar kecil, form memiliki validasi dekat input, status dibedakan secara konsisten, dan aksi berisiko menampilkan konfirmasi serta alasan wajib.

## Batas Implementasi Paket Ini
Paket awal adalah fondasi executable dan testable untuk aturan domain kritikal, migration skema inti, UI utama, API integrasi, dan posting framework. Integrasi bank, pajak negara spesifik, payroll, manufacturing, dan konsolidasi eliminasi penuh berada di fase lanjutan karena memerlukan kebijakan bisnis tambahan.
