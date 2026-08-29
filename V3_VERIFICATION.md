# CGOne ERP V3 Verification Notes

Build date: 2026-08-28

## Verified in build environment

Fresh verification was run after the Price Approval implementation and final PostgreSQL protection changes:

- V2 compatibility contract: PASS.
- V3 Inventory/Pricing contract: PASS.
- V3 Price Hold contract: PASS.
- V3 Price Approval contract: PASS.
- V3 finalization contract: PASS.
- Source integrity contract: PASS.
- Document rules: PASS.
- Posting math: PASS.
- Laravel 12 Blade scan: no inline `@php(...)` directives found.
- PHP syntax lint: 210 PHP files, 0 syntax errors.
- Price Update Excel template is present and contains: Item Code, Price Level, UOM, New Price, Effective Date, Notes.

## Price Approval control verified by source contracts

- Dynamic Price Level columns are driven by `price_levels.sort_order`.
- Approval is selectable per Item within a released batch.
- Each Price Update Line records `PENDING / APPROVED / REJECTED`, decision user/time, and rejection reason.
- `price_hold` is released per Item only when no other released pending price line remains for that Item.
- PostgreSQL V3 trigger freezes commercial price values after Release while still allowing the controlled PENDING → APPROVED/REJECTED decision fields.
- Finalized APPROVED/REJECTED/COMPLETED price records are protected from later edits through the V3 database trigger script.

## Environment limitation

This build workspace does not contain the target project's `vendor/` directory, therefore runtime Laravel commands such as these could not be executed here:

```text
php artisan test
php artisan route:list
php artisan migrate
```

Run the deployment verification commands in `V3_DEPLOYMENT.md` on the user's working Laravel 12 installation after extracting the overlay.
