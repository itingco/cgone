from pathlib import Path
root=Path(__file__).resolve().parents[2]
checks={
 'data view migration':'database/migrations/2026_08_28_000100_create_v3_inventory_and_views.php',
 'pricing migration':'database/migrations/2026_08_28_000200_create_v3_pricing_and_master_expansion.php',
 'data view service':'app/Services/DataViews/DataViewService.php',
 'location model':'app/Models/Location.php',
 'bin model':'app/Models/LocationBin.php',
 'transfer request controller':'app/Http/Controllers/Inventory/GoodsTransferRequestController.php',
 'transfer controller':'app/Http/Controllers/Inventory/GoodsTransferController.php',
 'transfer posting':'app/Services/Inventory/GoodsTransferPostingService.php',
 'price service':'app/Services/Pricing/ItemPriceService.php',
 'price batch service':'app/Services/Pricing/PriceUpdateApprovalService.php',
 'xlsx parser':'app/Services/Pricing/SimpleXlsxReader.php',
 'price update controller':'app/Http/Controllers/Pricing/PriceUpdateController.php',
 'price template':'storage/app/templates/price-level-update-template.xlsx',
}
missing=[f'{name}: {path}' for name,path in checks.items() if not (root/path).exists()]
if missing:
 print('MISSING\n'+'\n'.join(missing)); raise SystemExit(1)
texts={p:(root/p).read_text(errors='ignore') for p in ['routes/web.php','database/seeders/SecuritySeeder.php','app/Http/Controllers/Sales/SalesDocumentController.php']}
required=['goods-transfer-requests','goods-transfers','price-updates','data-views']
for token in required:
 if token not in texts['routes/web.php']:
  print('route token missing',token); raise SystemExit(1)
for token in ['inventory.locations','inventory.transfers','pricing.price-levels','pricing.price-updates']:
 if token not in texts['database/seeders/SecuritySeeder.php']:
  print('security token missing',token); raise SystemExit(1)
if 'price_hold' not in texts['app/Http/Controllers/Sales/SalesDocumentController.php']:
 print('sales eligibility does not mention price_hold'); raise SystemExit(1)
print('V3 inventory/pricing contract OK')
