# CG_one - Performance Optimization Build

This build keeps the existing Laravel ERP feature set and focuses on reducing request/query overhead.

## Main changes

1. **Ledger filter UI uses Bootstrap Collapse**
   - Advanced Columns / Filters / Sorting is collapsed by default.
   - Automatically opens when filters/sorts are active.
   - No modal/backdrop or extra AJAX dependency.

2. **Permission lookup memoization**
   - `MenuAuthorizationService` caches role IDs and permission decisions for the lifetime of one HTTP request.
   - Avoids repeated role/permission queries from menus, buttons and ledger row rendering.

3. **No authorization query inside ledger row loops**
   - Reverse permission is resolved once in `LedgerController` and passed to Blade.

4. **Lean ledger queries**
   - Ledger lists select only columns needed by the current views.
   - Related item/customer/vendor/location/bin records only load the IDs/codes required for display.

5. **Index-friendly date filtering**
   - Date filters use datetime ranges instead of wrapping indexed datetime columns in `DATE()` / `CAST()` whenever the date can be parsed.

6. **Posted-document N+1 removed**
   - Shipment/receipt invoice-availability checks are batched into one aggregate query instead of one SUM query per document line.
   - Posted-document detail no longer eager-loads unused `lines.item` records.

7. **Ledger view indexes**
   - Adds standalone `posting_at` indexes for Item, Customer, Vendor and GL ledger screens.
   - Adds Location+Posting and Bin+Posting indexes for Item Ledger filtering.

## Clean source package

To keep the package small and safe, it intentionally does **not** contain:

- `.env` (server credentials/secrets)
- `vendor/` (restore with Composer)
- runtime logs/cache/session files

`.env.example`, `composer.json` and `composer.lock` are retained.

## Recommended deployment

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env             # only for a new installation; keep existing .env on an existing server
php artisan key:generate         # new installation only
php artisan migrate --force
php artisan optimize
```

For an existing production server, **do not overwrite the existing `.env`** and **do not run `migrate:fresh`**.

If configuration/routes are changed later, refresh caches with:

```bash
php artisan optimize:clear
php artisan optimize
```
