# CGOne ERP Reporting Module — Design Specification

**Project:** CGOne ERP  
**Design status:** Approved in discussion  
**Design date:** 30.08.2026  
**Planned application release label:** Version 30.08.2026  
**Database platform:** PostgreSQL  
**Reporting architecture:** Hybrid Report Engine

---

## 1. Purpose

CGOne ERP membutuhkan reporting subsystem yang:

1. menyediakan laporan standar resmi untuk setiap modul ERP;
2. memungkinkan user membuat custom report tanpa SQL melalui Visual Report Builder;
3. menyediakan Advanced SQL Report untuk Administrator/IT;
4. mempunyai kontrol akses per user dan role;
5. mendukung filter, grouping, subtotal, grand total, drill-down, saved views, favorites, export, print, dan audit execution;
6. mengikuti database aktif yang dipilih pada header aplikasi;
7. memperlakukan Business Unit sebagai field/dimension pada record, bukan sebagai hierarchy atau global report selector.

---

## 2. Core Architecture

CGOne menggunakan **Hybrid Report Engine**.

Terdapat tiga jenis report:

### STANDARD
Laporan resmi ERP dengan calculation/query yang dikontrol aplikasi.

Contoh:
- General Ledger
- Trial Balance
- Balance Sheet
- Profit & Loss
- Customer Aging
- Vendor Aging
- Stock Card
- Stock Valuation
- COGS Detail
- Reconciliation Reports

### VISUAL
Custom report yang dibuat user melalui Visual Report Builder.

User tidak memilih nama table atau menulis JOIN.

### SQL
Advanced SQL Report khusus Administrator/IT.

Hanya query read-only yang diperbolehkan.

Semua jenis report memakai layer yang sama untuk:
- permission;
- filter;
- saved view;
- export;
- favorites;
- audit;
- report center.

---

## 3. Database Context

Database dipilih hanya melalui selector di header aplikasi.

Contoh:

- CGOne ERP
- CGOne ERP Medan
- CGOne ERP Jakarta

Report selalu dijalankan pada database aktif.

Definisi Standard Report berasal dari aplikasi/seeder sehingga tersedia pada setiap database CGOne yang mempunyai schema yang sama.

Custom Report, saved view, permission report, favorite, dan execution log disimpan pada database aktif masing-masing.

---

## 4. Business Unit Rule

Business Unit adalah **field/dimension yang tersimpan pada record**.

Business Unit:

- dapat ditampilkan sebagai kolom;
- dapat digunakan sebagai filter;
- dapat digunakan sebagai grouping;
- dapat digunakan sebagai pivot/comparison;
- dapat menjadi bagian dari subtotal.

Business Unit **tidak**:

- menjadi hierarchy Report Center;
- menjadi global report selector;
- dipilih sebelum membuka laporan;
- menjadi level akses report secara otomatis.

Contoh:

```text
Report: Sales Detail

Filters:
Date          01/08/2026 - 31/08/2026
Business Unit MEDAN
Warehouse     KATAMSO
Brand         INGCO
```

Jika Business Unit tidak difilter, report menampilkan seluruh record yang memenuhi filter lainnya.

---

## 5. Report Center

Satu section utama:

**Reports**

Submenu/area:

- Report Center
- Standard Reports
- My Reports
- Shared Reports
- Report Builder
- Advanced SQL Reports
- Report Administration

Kategori:

- Sales
- Purchase
- Inventory
- Pricing
- Finance
- Master
- Approval & Audit
- Management
- Custom

Report yang tidak dapat diakses user tidak ditampilkan.

Report Center mempunyai:

- Search
- Favorites
- Recently Used
- Category
- New Report (sesuai permission)

---

## 6. Standard Report Catalog

### 6.1 Sales / AR

1. Sales Summary
2. Sales Detail
3. Sales by Customer
4. Sales by Item
5. Sales by Salesperson
6. Sales by Warehouse / Business Unit
7. Sales Order Outstanding
8. Shipment Not Invoiced
9. Customer Outstanding
10. Customer Aging
11. Customer Statement
12. Sales Return / Credit (saat modul tersedia)

Sales metrics:

- Quantity
- Gross Sales
- Discount
- Net Sales
- COGS
- Gross Profit
- Margin %

Comparisons:

- Current Period vs Previous Period
- Current Period vs Same Period Last Year

---

### 6.2 Purchase / AP

1. Purchase Summary
2. Purchase Detail
3. Purchase by Supplier
4. Purchase by Item
5. Purchase by Warehouse / Business Unit
6. Purchase Order Outstanding
7. Receipt Not Invoiced
8. Supplier Outstanding
9. Vendor Aging
10. Vendor Statement

---

### 6.3 Inventory

1. Stock Availability
2. Stock On Hand
3. Stock Movement Detail
4. Stock Card
5. Stock Valuation
6. Inventory Aging
7. Slow Moving
8. Fast Moving
9. Dead Stock
10. Reorder Report
11. Negative Stock
12. Transfer History
13. COGS Detail

---

### 6.4 Pricing

1. Current Price List
2. Price History
3. Customer Price Assignment
4. Margin Analysis
5. Price Exception

---

### 6.5 Finance / GL

1. General Ledger Detail
2. Journal Register
3. Trial Balance
4. Balance Sheet
5. Profit & Loss
6. P&L by Business Unit
7. Account Movement
8. Cash Flow
9. AR vs GL Reconciliation
10. AP vs GL Reconciliation
11. Inventory vs GL Reconciliation

Official accounting formulas are locked.

User may change filters, grouping, period comparison, visible columns, saved views, and exports, but may not change the core accounting formula of official financial reports.

---

### 6.6 Master Data

1. Item Master
2. Customer Master
3. Supplier Master
4. Chart of Accounts
5. Master Change History
6. Inactive / Blocked Master

---

### 6.7 Approval & Audit

1. Approval Pending
2. Approval History
3. User Activity
4. Document Audit Trail
5. User Access Report
6. Role Access Matrix

---

### 6.8 Management

1. Executive Sales Dashboard
2. Executive Purchase Dashboard
3. Inventory Health
4. Working Capital
5. Monthly Performance
6. Business Unit Performance

---

## 7. Standard Report UI

Every report follows a common structure:

1. Report title
2. Database active
3. Period / As Of Date
4. Generated By
5. Generated At
6. Collapsible filters
7. KPI/Summary cards when applicable
8. Data table
9. Group subtotal
10. Grand total
11. Drill-down
12. Export
13. Save View

Actions:

- Filter
- Refresh
- Excel
- CSV
- PDF
- Print

---

## 8. Visual Report Builder

User selects a registered datasource.

User never directly sees database tables or writes JOIN conditions.

Example datasource:

**Sales Invoice Detail**

Field groups:

### Document
- Invoice No
- Invoice Date
- Posting Date
- Status

### Customer
- Customer Code
- Customer Name
- Customer Group
- City

### Item
- Item Code
- Item Name
- Category
- Brand
- UOM

### Dimensions
- Warehouse
- Business Unit
- Salesperson
- Price Level

### Values
- Quantity
- List Price
- Discount
- Net Price
- Sales Amount
- COGS
- Gross Profit
- Margin %

---

## 9. Visual Builder Features

### Columns
User selects visible columns.

### Filters

Operators:

- equals
- not equals
- contains
- starts with
- ends with
- >
- >=
- <
- <=
- between
- in
- not in
- is blank
- is not blank

AND / OR supported.

### Grouping

Maximum recommended 4 grouping levels.

Example:

Business Unit → Warehouse → Brand → Item

### Aggregation

Supported:

- SUM
- COUNT
- AVG
- MIN
- MAX

### Calculated Fields

Safe visual calculations:

- +
- -
- ×
- /
- %

Example:

Gross Profit = Sales Amount - COGS

Margin % = Gross Profit / Sales Amount × 100

Complex calculations remain under Standard Report or Advanced SQL.

---

## 10. Visual Output

Supported output modes:

- Table
- Summary Cards
- Column Chart
- Bar Chart
- Line Chart
- Pie / Donut Chart

Chart support is intentionally limited initially.

---

## 11. Saved Reports and Views

Custom reports store:

- Name
- Description
- Category
- Datasource
- Columns
- Filters
- Grouping
- Sorting
- Calculations
- Chart config
- Visibility
- Owner
- Created By
- Created At
- Updated By
- Updated At

Default custom report visibility:

**PRIVATE**

Visibility values:

- PRIVATE
- SHARED
- COMPANY

---

## 12. Report Permissions

Report-level permissions:

- View
- Export
- Print
- Edit
- Share
- Clone
- Delete
- Manage

Access can be granted to:

- Everyone
- Specific Role
- Specific User

Role and user access may be combined.

Standard reports cannot be deleted by normal users.

---

## 13. Advanced SQL Reports

Advanced SQL Report is available only to users with explicit SQL reporting permission.

Recommended permission codes:

- reports.sql.view
- reports.sql.create
- reports.sql.edit
- reports.sql.execute

Normally granted only to:

- Administrator
- IT

---

## 14. SQL Security

Only read queries are allowed.

Accepted starting statements:

- SELECT
- WITH

Blocked operations include:

- INSERT
- UPDATE
- DELETE
- MERGE
- DROP
- ALTER
- TRUNCATE
- CREATE
- GRANT
- REVOKE
- COPY
- CALL
- DO

Multiple statements are rejected.

SQL parameters must use binding, not string concatenation.

Example:

```sql
WHERE document_date BETWEEN :start_date AND :end_date
  AND business_unit_id = :business_unit
```

Recommended database hardening:

Create a PostgreSQL reporting role with SELECT-only privileges for Advanced SQL execution.

---

## 15. SQL Performance Protection

Advanced SQL:

- Preview row limit
- Screen row limit
- Query timeout
- Execution audit
- Error logging

Initial recommendation:

- Preview: 500 rows
- Screen: 5,000 rows
- Timeout: 60 seconds

Large result sets should use export rather than browser rendering.

---

## 16. Report Execution Audit

Every report execution logs:

- Report
- Report Type
- User
- Database
- Parameters
- Filters
- Started At
- Finished At
- Duration
- Row Count
- Export Type
- Success / Failed
- Error
- IP Address

---

## 17. Report Versioning

Changes to report definitions create version history.

Store:

- Report ID
- Version Number
- Definition snapshot
- Changed By
- Changed At
- Change Note

Initial scope provides history visibility.

Automatic rollback may be added later.

---

## 18. Drill-down

Official datasource fields may define drill-down links.

Examples:

Sales Summary  
→ Customer  
→ Sales Detail  
→ Posted Sales Invoice  
→ Item  
→ Item Ledger

Trial Balance  
→ Account  
→ General Ledger  
→ Source Document

Custom raw SQL does not automatically receive arbitrary drill-down.

---

## 19. Export

### Excel (.xlsx)

Primary export format.

Header includes:

- CGOne ERP
- Report Name
- Database
- Period
- Filters
- Generated By
- Generated At

Then:

- data;
- subtotal;
- grand total.

### CSV
Raw data oriented export.

### PDF
Management / official printable report.

### Print
Print-friendly layout.

---

## 20. Date and Number Format

Preferred Indonesian display:

Date:

`30/08/2026`

Report header may use:

`30 August 2026`

Currency:

`Rp 1.250.000,00`

Large table numeric cell:

`1.250.000,00`

Quantity decimal precision should follow UOM/system configuration where available.

---

## 21. Detailed Accounting Rules

### General Ledger

Must include opening balance before period.

Opening + Debit - Credit = Closing.

### Trial Balance

Columns:

- Opening Debit
- Opening Credit
- Period Debit
- Period Credit
- Ending Debit
- Ending Credit

Total Debit must equal Total Credit.

### Balance Sheet

Uses **As Of Date**, not From/To.

### Profit & Loss

Supports:

- Current Month
- YTD
- Previous Period
- Same Period Last Year
- Variance
- Variance %

### Customer / Vendor Aging

Must use Due Date.

Due Date derives from invoice/payment terms.

No fake aging calculation from last posting date.

### Customer / Vendor Statement

Must include opening balance before selected period and running balance.

---

## 22. Detailed Inventory Rules

### Stock Card

Must include:

- Opening Qty
- Opening Value
- Qty In
- Qty Out
- Running Qty
- Running Value
- Closing Qty
- Closing Value

### Stock Valuation

Should allow traceability from closing value back to transaction/value movements.

### Inventory Aging

Default buckets:

- 0–30
- 31–60
- 61–90
- 91–180
- 181–365
- >365

Support both quantity and value aging.

### Reorder

Initial formula:

If Stock <= Reorder Level:

Suggested Order = MAX(Min Order, Max Quantity - Stock)

Future enhancement may include average sales and lead time.

---

## 23. Reconciliation

Required control reports:

### AR vs GL
Customer subledger vs AR control account.

### AP vs GL
Vendor subledger vs AP control account.

### Inventory vs GL
Inventory valuation vs inventory control account.

Output:

- Subledger
- GL
- Difference
- Status

Difference = 0 → Reconciled.

---

## 24. Reporting Data Model

Planned reporting tables:

- report_definitions
- report_datasources
- report_fields
- report_user_access
- report_role_access
- report_favorites
- report_saved_views
- report_execution_logs
- report_versions

Exact physical schema may be refined during implementation while preserving this design contract.

---

## 25. Performance Strategy

Initial strategy:

1. Correct query
2. Appropriate indexes
3. Date defaults
4. Pagination
5. Limited browser rows

Do not create materialized views prematurely.

If real data volume requires it later, a datasource may be optimized using:

- database view;
- materialized view;
- summary table.

Relevant indexes should be reviewed for:

- posting_at
- document_date
- business_unit_id
- account_id
- item_id
- customer_id
- vendor_id
- warehouse_id

---

## 26. Default Period Behavior

Sales/Purchase/Inventory transaction reports:

**This Month**

General Ledger:

**Current Month**

Balance Sheet:

**As Of Today**

This avoids loading the entire ERP dataset by default.

---

## 27. Existing CGOne Components to Reuse

The implementation should reuse and extend the existing:

- Data View filter engine
- Saved views concept
- Menu authorization service
- Role / User permission infrastructure
- Existing standard ReportController logic
- Existing report table UI where useful
- Current active database context

Existing reports should be migrated into the new engine rather than silently removed.

---

## 28. Supporting Data Gaps

Some official reports require data that may not yet exist.

Examples:

### Aging
Requires due_date / payment term based due calculation and payment application/reference.

### Stock Available
Requires reservation/allocation if reserved stock should reduce availability.

If required source data does not exist, the report must clearly wait for the supporting field/process rather than generate misleading numbers.

---

## 29. Application Version Display

Login screen is simplified.

Release version is fixed per deployment, not based on current computer date.

Example:

`Version 30.08.2026`

A later release may show:

`Version 03.09.2026`

This supports troubleshooting from screenshots.

---

## 30. Implementation Phases

### Phase 1
- Report Center
- reporting database schema
- report permissions
- favorites
- standard engine foundation
- export framework

### Phase 2
- Standard Sales reports
- Standard Purchase reports
- Standard Inventory reports
- Standard Finance reports

### Phase 3
- Visual Report Builder
- saved views
- grouping
- subtotal
- visual calculations

### Phase 4
- Advanced SQL
- SQL security
- parameter binding
- execution auditing

### Phase 5
- PDF/Excel refinement
- drill-down
- comparative reports
- reconciliation

### Phase 6
- Performance optimization based on real production data

Distribution remains cumulative ZIP:

**download → extract → copy/overwrite → one post-copy command**

---

## 31. Acceptance Principles

The Reporting Module is accepted when:

1. Standard reports produce consistent official calculations.
2. Visual reports can be created without database knowledge.
3. Advanced SQL is restricted to authorized IT/Admin.
4. Report access is configurable by role and/or user.
5. Database follows active header selector.
6. Business Unit behaves only as record dimension/filter/group field.
7. Report execution is auditable.
8. Excel/CSV/PDF/Print are consistently available according to permission.
9. Reports do not expose unauthorized report definitions.
10. Large report execution has protective limits.
11. Official financial formulas cannot be freely rewritten by ordinary report users.
12. Existing CGOne reports are preserved or migrated without losing their intended functionality.

