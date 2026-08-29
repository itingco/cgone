# CGOne ERP V2 Verification Notes

Verification performed in the artifact workspace on 2026-08-18.

## Verified statically in the artifact workspace

- PHP syntax lint across `app`, `database`, `routes`, and `tests`.
- V2 contract checks for required migrations, routes, UI hooks, posting services, immutable SQL, permissions, and document flow.
- Pure PHP domain tests for document-state transitions, partial quantity math, and number-series formatting.
- Source-integrity checks for:
  - atomic posting service transaction boundaries
  - source-row locking during partial posting
  - deferred invoice-to-posted-line foreign keys
  - scoped UNDO ledger lookup by document type
  - durable reversal document number
  - PostgreSQL immutable targets
  - custom Number Series preservation by seeder
- `composer.json` parses as valid JSON and targets PHP `^8.2` / Laravel `^12.0` in the full source workspace.

## Runtime boundary

The artifact workspace does not contain `vendor/` and Composer is not available in the execution container. Therefore `php artisan migrate`, `php artisan route:list`, Blade compilation, and `php artisan test` cannot be executed here.

Run these on the working local CGOne installation after applying the overlay:

```powershell
cd C:\xampp82\htdocs\CGOne
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\SecuritySeeder
php artisan db:seed --class=Database\\Seeders\\MasterReferenceSeeder
php artisan route:list
php artisan test
```

Then apply and verify PostgreSQL immutable triggers as documented in `V2_DEPLOYMENT.md`.
