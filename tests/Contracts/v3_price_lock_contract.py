from pathlib import Path
root=Path(__file__).resolve().parents[2]
errors=[]
service=(root/'app/Services/Pricing/PriceUpdateApprovalService.php').read_text(errors='ignore')
if "update(['price_hold'=>true])" not in service: errors.append('price hold is not set on release')
for status in ["status'=>'APPROVED","status'=>'REJECTED"]:
    if status not in service: errors.append('missing final state '+status)
if 'clearHolds' not in service: errors.append('price hold clear logic missing')
price_service=(root/'app/Services/Pricing/ItemPriceService.php').read_text(errors='ignore')
if 'price approval pending' not in price_service: errors.append('eligibility does not reject price hold')
for f in [
 'app/Http/Controllers/Sales/SalesDocumentController.php',
 'app/Http/Controllers/Purchase/PurchaseDocumentController.php',
 'app/Http/Controllers/Inventory/GoodsTransferRequestController.php',
 'app/Http/Controllers/Inventory/GoodsTransferController.php',
 'app/Services/Inventory/GoodsTransferPostingService.php',
]:
    text=(root/f).read_text(errors='ignore')
    if 'assertEligible' not in text and 'price_hold' not in text: errors.append(f+' does not enforce item eligibility')
migration=(root/'database/migrations/2026_08_28_000200_create_v3_pricing_and_master_expansion.php').read_text(errors='ignore')
for token in ['item_prices','effective_from','effective_to','price_update_batches','price_update_lines','price_hold','customer_price_level_assignments']:
    if token not in migration: errors.append('pricing migration missing '+token)
controller=(root/'app/Http/Controllers/Pricing/PriceUpdateController.php').read_text(errors='ignore')
for token in ['mimes:xlsx','duplicate Item / Price Level / UOM','Uploader cannot approve their own price update','UOM is not assigned']:
    if token not in controller: errors.append('price update controller missing '+token)
if not (root/'storage/app/templates/price-level-update-template.xlsx').exists(): errors.append('xlsx template missing')
if errors:
    print('V3 PRICE LOCK CONTRACT FAIL')
    print('\n'.join(errors))
    raise SystemExit(1)
print('V3 price lock contract OK')
