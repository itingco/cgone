# CGOne Reporting Performance Strategy

R6 does not introduce materialized views because no production execution-log evidence was available in the package-building environment to justify one.

The cumulative package adds indexes aligned with repeatedly used reporting predicates:

- `gl_batches (business_unit_id, posting_at)`
- `posted_sales_invoices (document_date, business_unit_id)`
- `posted_purchase_invoices (document_date, business_unit_id)`
- `customer_ledgers (customer_id, business_unit_id, posting_at)`
- `vendor_ledgers (vendor_id, business_unit_id, posting_at)`
- `item_ledgers (item_id, business_unit_id, posting_at)`
- `item_ledgers (business_unit_id, posting_at)`
- `report_execution_logs (status, started_at, report_definition_id)`

These match existing standard-report date/entity/Business Unit access patterns without changing report calculations.

After production usage, use **Reports → Report Administration → Performance Monitor** to review P95 duration before considering additional indexes or summaries.
