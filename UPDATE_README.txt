CGOne - Reference Master Pages Update v6
========================================
Basis tampilan/struktur:
- Referensi screenshot Google Drive yang diberikan pada 30 Aug 2026.
- Item, Customer, dan Supplier/Vendor dibuat dengan pola record header + tab + history/summary seperti referensi.
- Sales reference ditempatkan pada tab Sales di Customer (Sales History, Outstanding Sales Orders, Uninvoiced Shipment).

Cara pasang:
1. Backup folder project dan database lokal.
2. Extract ZIP ini.
3. Copy seluruh isi hasil extract ke ROOT project CGOne dan pilih Replace/Overwrite.
4. Jalankan APPLY_UPDATE.bat satu kali.
5. Login dan cek Master > Items, Customers, Vendors.

Perubahan Item:
- Tab Details, GL Interface, Properties, Balance, Planning, Prices, Aliases, History & Statistics, Specification & Remarks, Audit Trail.
- Field baru: class/family/sub-family/manufacturer/price group, ukuran/berat/volume, warranty, reward point, planning, markup, default warehouse, dan properti item.
- Maintain UOM level 2-4 + conversion, Sales/Purchase UOM.
- Maintain Alias/Barcode.
- Stock by warehouse, annual summary, outstanding sales/purchase order.
- Price history dan Stock Value / COGS dari posted ledger.

Perubahan Customer:
- Detail billing/shipping sampai Kelurahan/Kecamatan/Kabupaten/ZIP.
- Additional Address multi-address.
- Default financial, sales, segmentation, dan rules/properties.
- Summary: balance, Customer Ledger, Receivable Aging, Pending Invoice, Payment History.
- Sales: Sales History, Outstanding Sales Orders, Uninvoiced Shipment.
- GL Interface, Remarks, Audit Trail.

Perubahan Supplier/Vendor:
- Detail supplier, identity, defaults, bank/payment settings.
- Additional Address multi-address.
- Summary: Supplier Ledger, Payable Aging, Pending Purchases, Payment History.
- Purchase History, Outstanding Purchase Orders, In-Transit Purchases.
- GL Interface, Remarks, Audit Trail.

Catatan penting:
- Pending invoice/purchase memakai ESTIMASI FIFO dari ledger existing karena database saat ini belum memiliki payment-allocation per invoice.
- Stock Value / COGS hanya membaca posted item ledger; update ini TIDAK mengubah costing engine.
- Field history/summary tidak disalin ke master, tetapi dibaca/dihitung dari ledger/document existing agar tidak terjadi duplikasi data.
- Tidak ada perubahan package Composer atau NPM.
- File .env dan database lokal tidak termasuk di paket.
