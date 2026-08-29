from pathlib import Path
import re, sys
root=Path(__file__).resolve().parents[2]
errors=[]
# Every V2 operational and posted table appears in a migration.
mig='\n'.join(p.read_text() for p in (root/'database/migrations').glob('*.php'))
required_tables=['sales_requests','sales_orders','shipments','sales_invoices','purchase_requests','purchase_orders','receipts','purchase_invoices','posted_shipments','posted_sales_invoices','posted_receipts','posted_purchase_invoices','posted_document_undos','inventory_posting_groups','general_product_posting_groups','customer_posting_groups','vendor_posting_groups','tax_posting_groups','posting_setups']
for t in required_tables:
    if f"Schema::create('{t}'" not in mig: errors.append(f'missing schema {t}')
# Literal Blade route names must exist in route declarations (dynamic route expressions are skipped).
routes=(root/'routes/web.php').read_text()
route_names=set(re.findall(r"->name\('([^']+)'\)",routes))
for slug in ['items','customers','vendors','coa','warehouses','uoms','price-levels']:
    route_names.update({f'master.{slug}.index',f'master.{slug}.export',f'master.{slug}.create',f'master.{slug}.store',f'master.{slug}.show',f'master.{slug}.edit',f'master.{slug}.update',f'master.{slug}.status'})
for blade in (root/'resources/views').rglob('*.blade.php'):
    text=blade.read_text()
    for name in re.findall(r"route\('([^'$]+)'",text):
        if name not in route_names:
            errors.append(f'{blade.relative_to(root)} references unknown route {name}')
# Posting services must use DB transaction and state mutation after posted creation.
for f in ['ShipmentPostingService.php','ReceiptPostingService.php','SalesInvoicePostingService.php','PurchaseInvoicePostingService.php']:
    text=(root/'app/Services/Posting'/f).read_text()
    if 'DB::transaction' not in text: errors.append(f'{f} not atomic')
    if 'markPosted' not in text: errors.append(f'{f} does not lock source as POSTED')
# Undo must use reversal and duplicate guard.
undo=(root/'app/Services/Posting/PostedDocumentUndoService.php').read_text()
for token in ['reversal_of_id','already been undone','markUndo','assertNoActiveDownstream']:
    if token not in undo: errors.append(f'undo missing {token}')
# PostgreSQL immutable targets.
sql=(root/'database/sql/postgresql_posted_document_protection.sql').read_text()
for t in ['posted_shipments','posted_sales_invoices','posted_receipts','posted_purchase_invoices','item_ledgers','customer_ledgers','vendor_ledgers','gl_batches','gl_entries']:
    if t not in sql: errors.append(f'protection missing {t}')

# Audit module names must be concrete; no template placeholder may leak into runtime logs.
for f in [root/'app/Http/Controllers/Sales/SalesDocumentController.php', root/'app/Http/Controllers/Purchase/PurchaseDocumentController.php']:
    text=f.read_text()
    if '{$modulePrefix}' in text: errors.append(f'{f.name} contains unresolved audit module placeholder')
# Operational invoice source IDs should receive FK constraints once posted line tables exist.
posted_mig=(root/'database/migrations/2026_08_18_000300_create_posted_documents.php').read_text()
for col,table in [('source_posted_shipment_line_id','posted_shipment_lines'),('source_posted_receipt_line_id','posted_receipt_lines')]:
    if f"foreign('{col}')" not in posted_mig or table not in posted_mig:
        errors.append(f'missing deferred FK for {col} -> {table}')
# Posting revalidation must lock source quantity rows during the atomic post transaction.
posting_lock_tokens={
    'ShipmentPostingService.php':'SalesOrderLine::lockForUpdate()',
    'ReceiptPostingService.php':'PurchaseOrderLine::lockForUpdate()',
    'SalesInvoicePostingService.php':'PostedShipmentLine::lockForUpdate()',
    'PurchaseInvoicePostingService.php':'PostedReceiptLine::lockForUpdate()',
}
for f,token in posting_lock_tokens.items():
    if token not in (root/'app/Services/Posting'/f).read_text(): errors.append(f'{f} does not lock source quantity row')
# UNDO ledger lookup must include document type, not only a potentially user-configurable document number.
undo=(root/'app/Services/Posting/PostedDocumentUndoService.php').read_text()
if "where('document_type',$documentType)" not in undo: errors.append('undo lookup is not scoped by document_type')


# Reversal must have a durable document number and be visible from posted history.
posted_mig=(root/'database/migrations/2026_08_18_000300_create_posted_documents.php').read_text()
if 'reversal_document_no' not in posted_mig: errors.append('undo table does not persist reversal_document_no')
undo_model=(root/'app/Models/Documents/PostedDocumentUndo.php').read_text()
if 'reversalGlBatch' not in undo_model: errors.append('undo model missing reversal GL relation')
posted_view=(root/'resources/views/posted/show.blade.php').read_text()
for token in ['Customer Ledger Entries','Vendor Ledger Entries','reversal_document_no']:
    if token not in posted_view: errors.append(f'posted show missing {token}')
# Operational detail must expose real related document flow, not only status labels.
if not (root/'app/Services/Documents/DocumentFlowService.php').exists(): errors.append('missing DocumentFlowService')
else:
    flow=(root/'app/Services/Documents/DocumentFlowService.php').read_text()
    for token in ['SalesOrder','Shipment','SalesInvoice','PurchaseOrder','Receipt','PurchaseInvoice']:
        if token not in flow: errors.append(f'document flow service missing {token}')
show=(root/'resources/views/documents/show-core.blade.php').read_text()
if 'Related Documents' not in show: errors.append('operational show missing Related Documents UI')
# Posting preview should show the expected posted number without consuming it.
seq=(root/'app/Services/System/DocumentSequenceService.php').read_text()
if 'previewNext' not in seq: errors.append('document sequence service missing previewNext')
preview=(root/'resources/views/posted/preview.blade.php').read_text()
if 'expectedPostedNo' not in preview: errors.append('posting preview missing expected posted number')
# Audit and undo records are immutable at DB level too.
protect=(root/'database/sql/postgresql_posted_document_protection.sql').read_text()
for table in ['posted_document_undos','activity_logs']:
    if table not in protect: errors.append(f'protection missing {table}')


# Posting groups are configuration, so existing mappings must be editable without direct DB changes.
posting_controller=(root/'app/Http/Controllers/Configuration/PostingSetupController.php').read_text()
posting_view=(root/'resources/views/configuration/posting-setup/index.blade.php').read_text()
if 'edit_type' not in posting_controller or 'edit_id' not in posting_controller: errors.append('posting setup controller missing edit selection')
if 'Edit' not in posting_view or 'name="id"' not in posting_view: errors.append('posting setup view missing edit workflow')
# Default number seeding must preserve user-customized formats.
seed=(root/'database/seeders/MasterReferenceSeeder.php').read_text()
if 'DocumentSequence::updateOrCreate' in seed: errors.append('number series seeder can overwrite user customization')


# Approved permission model includes delete and print; delete must not piggyback on edit.
security=(root/'database/seeders/SecuritySeeder.php').read_text()
for perm in ["'delete'","'print'"]:
    if perm not in security: errors.append(f'security seeder missing {perm} permission')
for f in [root/'app/Http/Controllers/Sales/SalesDocumentController.php',root/'app/Http/Controllers/Purchase/PurchaseDocumentController.php']:
    text=f.read_text()
    if "permit($type,'delete')" not in text: errors.append(f'{f.name} destroy does not require delete permission')
posted_view=(root/'resources/views/posted/show.blade.php').read_text()
if 'window.print()' not in posted_view: errors.append('posted document UI missing Print action')
# V3 Location replaces Warehouse for stock movements; a Location is mandatory before ledger posting.
for f,doc in [(root/'app/Services/Posting/ShipmentPostingService.php','Shipment'),(root/'app/Services/Posting/ReceiptPostingService.php','Receipt')]:
    text=f.read_text()
    if 'location_id' not in text or 'Location is required' not in text: errors.append(f'{doc} posting lacks friendly Location validation')
# Order progress should include both logistics and invoice progress.
partial=(root/'app/Services/Documents/PartialQuantityService.php').read_text()
for token in ['invoicedSalesOrderLine','invoicedPurchaseOrderLine']:
    if token not in partial: errors.append(f'partial service missing {token}')
# Shared posting orchestrator keeps controllers thin and centralizes document handler mapping.
if not (root/'app/Services/Posting/DocumentPostingService.php').exists(): errors.append('missing DocumentPostingService orchestration boundary')

if errors:
    print('\n'.join('FAIL '+x for x in errors)); sys.exit(1)
print('Source integrity OK')
