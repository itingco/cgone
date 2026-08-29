# CGOne ERP V4 — Verification Checklist

1. Login dan pastikan dashboard kosong untuk user yang belum punya konfigurasi.
2. Buka **Customize Dashboard**, aktifkan 1–3 widget, atur order/width, simpan.
3. Pastikan hanya widget terpilih tampil.
4. Buka Master Items: tombol **Filter** membuka panel offcanvas.
5. Buka satu Item: cek tabs General, Inventory & UOM, Purchasing, Sales, Price Level, Accounting, History.
6. Price Level: cek harga/UOM/effective date.
7. History Item: filter Sales/Purchase/Inventory dan periode.
8. Customer: cek Sales History.
9. Vendor: cek Purchase History.
10. Top-right Profile: Role, Department, Change Password.
11. Configuration > Users: Department dapat disimpan.
12. Database baru: Seeder COA menghasilkan 60 akun/heading. Database existing: seeder tidak menimpa COA.
13. Jalankan contract test `python tests/Contracts/v4_erp_optimization_contract.py`.
