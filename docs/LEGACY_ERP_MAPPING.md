# Legacy ERP Mapping Worksheet

File ini dipakai saat backup SQL Server existing sudah dapat dibaca.

| Domain | Existing Object | New Baseline Object | Decision | Notes |
|---|---|---|---|---|
| Item | - | `items` | Pending | Inspect item IDs, codes, UOM levels, account refs |
| Customer | - | `customers` | Pending | Inspect credit/account structures |
| Vendor | - | `vendors` | Pending | Inspect AP/account structures |
| COA | - | `chart_of_accounts` | Pending | Preserve account codes/hierarchy |
| Warehouse | - | `warehouses` | Pending | Preserve warehouse IDs/codes where needed |
| UOM | - | `uoms` | Pending | Map multi-level UOM structure |
| Price Level | - | `price_levels` | Pending | Map existing retail/HET/etc. concepts |
| Numbering | - | `document_sequences` | Pending | Compare existing numbering rules |
| Item Ledger | - | `item_ledgers` | Pending | Decide reuse vs compatibility view |
| Customer Ledger | - | `customer_ledgers` | Pending | Decide reuse vs compatibility view |
| Vendor Ledger | - | `vendor_ledgers` | Pending | Decide reuse vs compatibility view |
| GL | - | `gl_batches` / `gl_entries` | Pending | Inspect posting SP/view/function |

Allowed decisions: `REUSE`, `MAP/ADAPT`, `REBUILD`, `DEPRECATE`.
