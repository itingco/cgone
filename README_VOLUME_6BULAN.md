# CGOne Sample Company — Volume 6 Bulan

Patch ini hanya membawa:
- app/Console/Commands/SeedSampleCompany.php
- app/Services/Demo/DemoVolumeSchedule.php
- regression test standalone

Tidak membawa migration H1-H4, sehingga migration fix lokal tidak tertimpa.

## Default
Jika `--days` tidak diberikan:
- `--months=6`
- `--sales-per-day=12`
- `--purchase-per-day=6`
- `--inventory-per-day=4`

`--date` menjadi tanggal akhir rentang.

Contoh:
`--date=2026-09-05 --months=6`
menghasilkan enam bulan kalender:
2026-04-01 s/d 2026-09-05.

Dengan fixture default tersebut:
- 158 hari kalender
- sekitar 1.584 sales
- sekitar 794 purchase
- sekitar 528 inventory events

Jumlah aktual per hari dibuat deterministic:
- Senin-Jumat: volume normal dengan variasi +/-20%
- Sabtu: sekitar 60% volume
- Minggu: sekitar 25% volume

## Install
Extract/copy ke root CGOne lalu:

`php artisan optimize:clear`

Kemudian:

`php artisan erp:seed-sample-company --database=cgone_demo2 --company="PT CGOne Sample Indonesia" --date=2026-09-05`

Karena months default = 6, tidak wajib menulis `--months=6`.

## Custom
Contoh 12 bulan:

`php artisan erp:seed-sample-company --database=cgone_demo2 --date=2026-09-05 --months=12 --sales-per-day=15 --purchase-per-day=8 --inventory-per-day=5`

Limits:
- months: 1-24
- explicit days mode: 1-30
- sales-per-day: 0-200
- purchase-per-day: 0-100
- inventory-per-day: 0-100

Jika `--days` ditulis eksplisit, mode lama tetap berlaku:
`php artisan erp:seed-sample-company --database=cgone_demo2 --date=2026-09-03 --days=3`

## Data tambahan 6 bulan
Selain transaksi:
- attendance hari kerja untuk 3 employee
- monthly approved overtime sample
- periodic leave sample
- monthly bonus payroll input
- monthly loan deduction input
- H4 payroll sample periode akhir tetap dihitung bila H4 tersedia

## Idempotent
Nomor transaksi tetap deterministic dengan pola:
`DEMO-VOL-<TYPE>-YYYYMMDD-NNN`

Run ulang dengan parameter yang sama akan update DEMO record yang sama, bukan menggandakan nomor dokumen.

## Performance
Seeder sekarang cache metadata kolom per database/table supaya volume 6 bulan tidak terus-menerus memanggil schema introspection untuk setiap upsert.
