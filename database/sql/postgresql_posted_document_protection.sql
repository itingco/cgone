-- CGOne ERP V2 - PostgreSQL immutable accounting protection
-- Apply AFTER migrations. This blocks direct UPDATE/DELETE on posted snapshots and ledgers.
-- Corrections must be new reversal INSERTs through the application UNDO/Adjustment services.

CREATE OR REPLACE FUNCTION cgone_reject_immutable_change()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'CGOne ERP: % is immutable. Use UNDO/reversal instead of %.', TG_TABLE_NAME, TG_OP
        USING ERRCODE = '55000';
END;
$$;

DO $$
DECLARE
    tbl text;
    tables text[] := ARRAY[
        'posted_shipments','posted_shipment_lines',
        'posted_sales_invoices','posted_sales_invoice_lines',
        'posted_receipts','posted_receipt_lines',
        'posted_purchase_invoices','posted_purchase_invoice_lines',
        'posted_document_undos','activity_logs',
        'item_ledgers','customer_ledgers','vendor_ledgers','gl_batches','gl_entries'
    ];
BEGIN
    FOREACH tbl IN ARRAY tables LOOP
        IF to_regclass('public.' || tbl) IS NOT NULL THEN
            EXECUTE format('DROP TRIGGER IF EXISTS trg_cgone_immutable_%I ON %I', tbl, tbl);
            EXECUTE format(
                'CREATE TRIGGER trg_cgone_immutable_%I BEFORE UPDATE OR DELETE ON %I FOR EACH ROW EXECUTE FUNCTION cgone_reject_immutable_change()',
                tbl, tbl
            );
        END IF;
    END LOOP;
END $$;
