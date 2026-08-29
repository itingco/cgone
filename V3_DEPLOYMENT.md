# CGOne ERP V3 Deployment — Laravel 12 / PostgreSQL

Target existing project: `C:\xampp82\htdocs\CGOne`.

## 1. Backup first

Backup the PostgreSQL database and make a copy of the current project folder before extracting this update.

This package is an **overlay**. It intentionally does **not** contain `.env`, `vendor/`, `.git/`, `composer.json`, or `composer.lock`, so keep the Laravel 12 / PHP 8.2 installation that is already working.

## 2. Extract overlay

Extract the ZIP directly over:

```text
C:\xampp82\htdocs\CGOne
```

Allow Windows to replace files with the same name.

## 3. Clear Laravel caches

Open PowerShell:

```powershell
cd C:\xampp82\htdocs\CGOne
php artisan optimize:clear
php artisan view:clear
```

## 4. Run V3 migrations

Do **not** use `migrate:fresh`.

```powershell
php artisan migrate
```

Expected V3 migrations:

```text
2026_08_28_000100_create_v3_inventory_and_views
2026_08_28_000200_create_v3_pricing_and_master_expansion
2026_08_28_000300_add_line_approval_to_price_updates
```

Migration `000100` also makes legacy `warehouse_id` nullable on Item Ledger, Posted Shipment, and Posted Receipt because V3 stock transactions use `location_id` / `bin_id`.

## 5. Refresh menu/security and default reference data

```powershell
php artisan db:seed --class=Database\Seeders\SecuritySeeder
php artisan db:seed --class=Database\Seeders\MasterReferenceSeeder
```

The seeders use `firstOrCreate` / `updateOrCreate`; they do not reset custom Number Series.

## 6. Configure Posting Setup

Open:

```text
Configuration > Posting Setup
```

Set **Inventory In Transit Account** before posting Goods Transfers.

## 7. Install PostgreSQL V3 protection

Run after migrations. PostgreSQL 18 example:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" `
  -h 127.0.0.1 -p 5432 -U erp -d cgone_erp `
  -f "C:\xampp82\htdocs\CGOne\database\sql\postgresql_v3_protection.sql"
```

Then verify:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" `
  -h 127.0.0.1 -p 5432 -U erp -d cgone_erp `
  -f "C:\xampp82\htdocs\CGOne\database\sql\verify_postgresql_v3.sql"
```

The verification query should show all three V3 migrations as `1`, Location `IN-TRANSIT`, Price Levels, Posting Setup including `inventory_in_transit_account_id`, and V3 protection triggers.

## 8. Rebuild views and start

```powershell
php artisan optimize:clear
php artisan view:cache
php artisan serve
```

Open the application using the same host consistently, e.g. `http://127.0.0.1:8000`.

## 9. First V3 checks

1. **Pricing > Price Level Master** — create Price Levels such as MITRA, RETAIL, KHUSUS, GROSIR, PLATFORM and set `Order` from cheapest to most expensive.
2. **Pricing > Price Updates** — download Excel template and upload a test update.
3. Release the batch. Confirm affected Items show `PRICE HOLD` and cannot be processed.
4. **Pricing > Price Approval** — verify dynamic Price Level columns, Last Cost, Old/New Price, Effective Date, and Pending status.
5. Approve one selected Item. Confirm only that Item is unlocked if it has no other pending released price update.
6. Reject another Item with a reason. Confirm old approved price remains active and Item is unlocked.
7. **Inventory > Locations** — create both non-bin and Bin Mandatory Locations.
8. **Goods Transfer Request** — Release → Approve → Create Goods Transfer → Release → Ship → Receive.
9. Confirm Item Ledger movement is Source → `IN-TRANSIT` → Destination and GL uses Inventory In Transit.

## 10. Important controls

- Price Update uploader cannot approve/reject their own price batch.
- Price values cannot be edited from Price Approval. Wrong values must be rejected and uploaded again.
- Released Item price changes lock the Item immediately.
- Price history is effective-dated; old approved history is retained.
- Goods Transfer Request approval does not move stock.
- Stock moves only on Goods Transfer Ship/Receive.
- Posted ledger corrections use reversal/UNDO, not edit/delete.
- `Min` / `Max` pricing rules are intentionally not implemented yet.
