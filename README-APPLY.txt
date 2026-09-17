CGOne Ledger Update
===================

Perubahan:
1. Ledger tidak lagi menampilkan Action / Reverse.
2. Document Type ditampilkan dengan label manusiawi.
3. Document Number menjadi link ke posted document / goods transfer / adjustment bila sumber dapat ditemukan.
4. Item Ledger tidak lagi menampilkan Qty In dan Qty Out.
5. Item Ledger menampilkan satu Qty signed: Qty In - Qty Out.
6. Net Qty Movement summary mengikuti seluruh filter aktif, bukan hanya current page.
7. Customer/Vendor Ledger juga menampilkan Document Type.
8. General Ledger tidak lagi memiliki Reverse Batch dari halaman ledger.

Cara apply:
- Backup source terlebih dahulu.
- Extract ZIP ini ke ROOT project CGOne dan overwrite file yang sama.
- Tidak ada migration database.
- Jalankan:

  php artisan optimize:clear
  php artisan test --filter=LedgerBrowseTest
  php artisan test
  php artisan optimize

Catatan:
- Data qty_in / qty_out di database TIDAK dihapus. Hanya tampilan yang disederhanakan.
- Formula Qty tampilan = qty_in - qty_out.
- Adjustment tetap dilakukan dari menu Transactions > Adjustment, bukan dari halaman ledger.
