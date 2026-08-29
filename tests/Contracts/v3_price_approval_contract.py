from pathlib import Path
root=Path(__file__).resolve().parents[2]
errors=[]
required_files=[
 'app/Http/Controllers/Pricing/PriceApprovalController.php',
 'resources/views/pricing/price-approval/index.blade.php',
 'database/migrations/2026_08_28_000300_add_line_approval_to_price_updates.php',
]
for f in required_files:
    if not (root/f).exists(): errors.append('missing '+f)
route=(root/'routes/web.php').read_text(errors='ignore')
for token in ['price-approval.index','price-approval.approve-selected','price-approval.reject-selected']:
    if token not in route: errors.append('route missing '+token)
security=(root/'database/seeders/SecuritySeeder.php').read_text(errors='ignore')
sql=(root/'database/sql/postgresql_v3_protection.sql').read_text(errors='ignore')
for token in ['approval_status','COMPLETED','only approval decision fields may change after RELEASE']:
    if token not in sql: errors.append('PostgreSQL price approval protection missing '+token)
if 'pricing.price-approval' not in security: errors.append('security menu missing pricing.price-approval')
layout=(root/'resources/views/layouts/app.blade.php').read_text(errors='ignore')
if 'Price Approval' not in layout: errors.append('layout missing Price Approval')
service=(root/'app/Services/Pricing/PriceUpdateApprovalService.php').read_text(errors='ignore')
for token in ['approveItems','rejectItems','approval_status','refreshBatchStatus']:
    if token not in service: errors.append('approval service missing '+token)
line=(root/'app/Models/Pricing/PriceUpdateLine.php').read_text(errors='ignore')
for token in ['approval_status','approved_at','rejected_at']:
    if token not in line: errors.append('price line model missing '+token)
batch_view=(root/'resources/views/pricing/price-updates/show.blade.php').read_text(errors='ignore')
for token in ['Open Price Approval','approval_status']:
    if token not in batch_view: errors.append('price update batch view missing '+token)
index_view=(root/'resources/views/pricing/price-updates/index.blade.php').read_text(errors='ignore')
for token in ['Pending','Approved','Rejected']:
    if token not in index_view: errors.append('price update index missing '+token+' count')
if (root/'resources/views/pricing/price-approval/index.blade.php').exists():
    view=(root/'resources/views/pricing/price-approval/index.blade.php').read_text(errors='ignore')
    for token in ['Last Cost','Approve Selected','Reject Selected','priceLevels','effective_date','approval_status']:
        if token not in view: errors.append('approval view missing '+token)
    if 'Min' in view or 'Max' in view: errors.append('approval view should not implement Min/Max yet')
if errors:
    print('V3 PRICE APPROVAL CONTRACT RED')
    print('\n'.join(errors))
    raise SystemExit(1)
print('V3 price approval contract OK')
