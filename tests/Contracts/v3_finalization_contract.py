from pathlib import Path
root=Path(__file__).resolve().parents[2]
errors=[]
# Laravel 12-safe Blade: inline @php(...) caused prior production failure.
for p in (root/'resources/views').rglob('*.blade.php'):
    text=p.read_text(errors='ignore')
    if '@php(' in text:
        errors.append(f'inline @php in {p.relative_to(root)}')
# Posting setup must expose and validate in-transit account.
ctl=(root/'app/Http/Controllers/Configuration/PostingSetupController.php').read_text(errors='ignore')
view=(root/'resources/views/configuration/posting-setup/index.blade.php').read_text(errors='ignore')
for token in ['inventory_in_transit_account_id']:
    if token not in ctl: errors.append('posting setup controller missing '+token)
    if token not in view: errors.append('posting setup view missing '+token)
# Main menu must use Location and include V3 inventory/pricing workflows.
layout=(root/'resources/views/layouts/app.blade.php').read_text(errors='ignore')
for token in ['Locations','Goods Transfer Requests','Goods Transfers','Item Prices','Price Updates']:
    if token not in layout: errors.append('layout missing '+token)
if "'Warehouses'" in layout: errors.append('layout still presents Warehouses menu')
# V3 database scripts expected for deploy verification.
for path in ['database/sql/postgresql_v3_protection.sql','database/sql/verify_postgresql_v3.sql']:
    if not (root/path).exists(): errors.append('missing '+path)
verify_sql=(root/'database/sql/verify_postgresql_v3.sql').read_text(errors='ignore')
if '2026_08_28_000300_add_line_approval_to_price_updates' not in verify_sql: errors.append('V3 verify SQL missing line approval migration check')

# V3 posts by Location; legacy warehouse_id must be nullable on append-only ledgers/posted logistics.
migration=(root/'database/migrations/2026_08_28_000100_create_v3_inventory_and_views.php').read_text(errors='ignore')
for table in ['item_ledgers','posted_shipments','posted_receipts']:
    marker=f'ALTER TABLE {table} ALTER COLUMN warehouse_id DROP NOT NULL'
    if marker not in migration: errors.append('V3 migration does not make '+table+'.warehouse_id nullable')

if errors:
    print('V3 FINALIZATION CONTRACT RED')
    print('\n'.join(errors))
    raise SystemExit(1)
print('V3 finalization contract OK')
