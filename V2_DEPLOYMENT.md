# CGOne ERP V2 Update Deployment

Target: existing CGOne ERP installation already running Laravel 12 / PHP 8.2 / PostgreSQL with the six baseline migrations applied to `cgone_erp`.

## 1. Backup first

Back up the project folder and PostgreSQL database before overwriting files.

## 2. Apply the overlay

Extract `CGOne-ERP-V2-update.zip` directly over the CGOne project root, preserving folders.

No new Composer package is required by the V2 overlay, so an existing working Laravel 12 `vendor` directory can remain in place.

## 3. Clear application caches

```powershell
cd C:\xampp82\htdocs\CGOne
php artisan optimize:clear
```

## 4. Run the three V2 migrations

```powershell
php artisan migrate
```

Expected new migrations:

- `2026_08_18_000100_create_posting_setup_tables`
- `2026_08_18_000200_create_operational_documents`
- `2026_08_18_000300_create_posted_documents`

Then verify:

```powershell
php artisan migrate:status
php artisan route:list
```

## 5. Refresh security menus and number series

```powershell
php artisan db:seed --class=Database\\Seeders\\SecuritySeeder
php artisan db:seed --class=Database\\Seeders\\MasterReferenceSeeder
php artisan optimize:clear
```

Existing users, roles, master data, and ledger history are preserved. Default Number Series are created only when missing, so formats you customize later are not overwritten by re-running the seeder.

## 6. Configure posting before testing POST

Open **Configuration -> Posting Setup** and configure at minimum:

1. Default GRNI account.
2. Inventory Posting Group: Inventory + COGS accounts.
3. General Product Posting Group: Sales + Purchase accounts.
4. Customer Posting Group: AR account.
5. Vendor Posting Group: AP account.
6. Tax Posting Group when VAT/tax is used.

Then assign the appropriate Posting Groups to Item, Customer, and Vendor masters.

## 7. Review Number Series

Open **Configuration -> Number Series**. Default series are seeded for SR, SO, SH, PSH, SI, PSI, PR, PO, RC, PRC, PI, PPI, and UNDO.

Patterns can be changed, for example:

```text
SO/{YY}{MM}/{#####}
PSI/{YYYY}/{MM}/{#####}
```

Supported tokens: `{YYYY}`, `{YY}`, `{MM}`, `{DD}`, `{#####}`.

## 8. Apply PostgreSQL immutable protection

After the migrations are complete:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U erp -h 127.0.0.1 -p 5432 -d cgone_erp -f .\database\sql\postgresql_posted_document_protection.sql
```

Verify triggers:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U erp -h 127.0.0.1 -p 5432 -d cgone_erp -f .\database\sql\verify_postgresql_v2.sql
```

## 9. Suggested functional smoke test

1. Create/configure COA + Posting Setup.
2. Assign Posting Groups to an Inventory item, Customer, and Vendor.
3. Sales Request -> Release -> Sales Order -> Release -> Shipment -> Release -> Preview/Post.
4. Confirm Posted Shipment created Item Ledger and balanced GL.
5. Posted Shipment -> Create Sales Invoice -> Release -> Preview/Post.
6. Confirm Customer Ledger + GL.
7. Purchase Request -> Purchase Order -> Receipt -> Posted Receipt -> Purchase Invoice -> Posted Purchase Invoice.
8. Test partial quantity by posting less than the original order quantity, then process the remainder.
9. Test UNDO. Confirm original Posted Document remains visible and reversal ledger/GL entries are created.
