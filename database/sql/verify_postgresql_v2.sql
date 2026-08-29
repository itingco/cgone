SELECT schemaname, tablename, triggername
FROM pg_catalog.pg_triggers
WHERE triggername LIKE 'trg_cgone_immutable_%'
ORDER BY tablename;

SELECT code, format_pattern, reset_period, current_number
FROM document_sequences
WHERE code IN (
 'SALES_REQUEST','SALES_ORDER','SHIPMENT','POSTED_SHIPMENT','SALES_INVOICE','POSTED_SALES_INVOICE',
 'PURCHASE_REQUEST','PURCHASE_ORDER','RECEIPT','POSTED_RECEIPT','PURCHASE_INVOICE','POSTED_PURCHASE_INVOICE','UNDO'
)
ORDER BY code;
