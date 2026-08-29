-- CGOne ERP V3 verification
SELECT 'V3 migrations' AS check_name,
       COUNT(*) FILTER (WHERE migration='2026_08_28_000100_create_v3_inventory_and_views') AS inventory_view_migration,
       COUNT(*) FILTER (WHERE migration='2026_08_28_000200_create_v3_pricing_and_master_expansion') AS pricing_migration,
       COUNT(*) FILTER (WHERE migration='2026_08_28_000300_add_line_approval_to_price_updates') AS line_approval_migration
FROM migrations;

SELECT code,name,bin_mandatory,additional_discount_pct,is_system,is_active
FROM locations
ORDER BY is_system DESC,code;

SELECT code,name,sort_order,currency_code,is_active
FROM price_levels
ORDER BY sort_order,code;

SELECT code,grni_account_id,inventory_in_transit_account_id,inventory_adjustment_account_id
FROM posting_setups
WHERE code='DEFAULT';

SELECT COUNT(*) AS price_hold_items FROM items WHERE price_hold=true;

SELECT n.nspname AS schema_name,c.relname AS table_name,t.tgname AS trigger_name
FROM pg_catalog.pg_trigger t
JOIN pg_catalog.pg_class c ON c.oid=t.tgrelid
JOIN pg_catalog.pg_namespace n ON n.oid=c.relnamespace
WHERE t.tgisinternal=false
  AND (t.tgname LIKE 'trg_cgone_v3_%' OR t.tgname LIKE 'trg_cgone_guard_%')
ORDER BY c.relname,t.tgname;
