# ERP Laravel Baseline Design

Date: 2026-08-17
Status: Approved design baseline

## 1. Objective

Build a Laravel-based ERP baseline that can later be mapped to the user's existing SQL Server ERP backup. The new application must preserve useful legacy master-data structures while adding a safer modern application layer for permissions, posting, ledgers, adjustments, and auditability.

## 2. Technology Baseline

- Framework: Laravel 10
- PHP: 8.1
- Database: Microsoft SQL Server
- UI: Blade + Bootstrap-compatible admin layout
- Authentication: Laravel session authentication
- Authorization: Role + menu + granular permission middleware
- Ledger integrity: enforced in both application services and SQL Server triggers

## 3. Architectural Approach

Use a hybrid compatibility architecture:

1. Master data follows the shape and naming concepts of the legacy ERP where practical.
2. New application services isolate business rules from direct table manipulation.
3. Transaction posting writes immutable ledger entries.
4. Corrections use reversal/adjustment transactions instead of edit/delete.
5. Security, configuration, and audit features are implemented as first-class ERP modules.

The legacy database backup is treated as a reference and future mapping source, not something to copy blindly into the new project.

## 4. Initial Module Scope

### 4.1 Dashboard

Provide summary cards and recent activity. No analytical BI dependency is required in the baseline.

### 4.2 Master Data

Initial modules:

- Items
- Customers
- Vendors
- Chart of Accounts
- Warehouses
- Units of Measure
- Price Levels
- Tax and general configuration foundation

Each supported master provides:

- Browse/list
- Search/filter
- View detail
- Create
- Edit while allowed
- Activate/deactivate
- Export-ready service boundary

Hard delete is not exposed for master records already referenced by transactions.

### 4.3 Security / Configuration

Provide:

- Users
- Roles
- Menus
- Permissions
- Role-menu-permission assignments
- System settings
- Document numbering/sequence configuration

Initial granular permissions:

- view
- create
- edit
- approve
- post
- reverse
- export

Ledger modules do not expose edit or delete permission after posting.

## 5. Transaction Lifecycle

Standard transaction lifecycle:

DRAFT -> APPROVED -> POSTED

Rules:

- Draft may be edited by authorized users.
- Approved transactions are restricted according to module policy.
- Posted transactions are immutable.
- Posting writes one or more ledger entries.
- Posted transactions can only be corrected through reversal/adjustment.

## 6. Immutable Ledger Design

Initial ledger types:

- Item Ledger
- Customer Ledger
- Vendor Ledger
- General Ledger

For all posted ledger rows:

- UPDATE is forbidden.
- DELETE is forbidden.
- Laravel repositories/services do not expose update/delete operations.
- SQL Server triggers reject direct UPDATE/DELETE attempts.

Every ledger row records at minimum:

- unique identifier
- posting date/time
- source module
- source document type
- source document number/id
- reference/master id
- debit/credit or in/out values as applicable
- description
- created/posting user
- reversal link when applicable
- timestamps

## 7. Adjustment / Reversal Engine

Corrections are represented as new transactions.

### 7.1 Inventory Example

Original: Qty Out 10

Reversal: Qty In 10

Corrected entry, when required: Qty Out 8

Resulting ledger history remains complete and auditable.

### 7.2 GL Example

Original:

Dr Inventory 1,000,000
Cr AP        1,000,000

Reversal:

Dr AP        1,000,000
Cr Inventory 1,000,000

A corrected journal may then be posted separately.

### 7.3 Adjustment Requirements

Every adjustment stores:

- original transaction/document reference
- reversal transaction/document reference
- adjustment reason
- user performing the adjustment
- date/time
- optional approval metadata

## 8. Activity Logging

All important user actions are logged.

Minimum log data:

- user
- module
- action
- target type
- target id/document number
- IP address when available
- request path
- before data where applicable
- after data where applicable
- adjustment/reversal references when applicable
- timestamp

Actions to log include:

- login/logout where practical
- create master
- update master
- activate/deactivate
- approve
- post
- reverse
- security/permission changes
- configuration changes

## 9. Data Model Baseline

### Master

- items
- customers
- vendors
- chart_of_accounts
- warehouses
- uoms
- price_levels

### Security

- users
- roles
- menus
- permissions
- role_menu_permissions
- user_roles (or role_id on users if single-role baseline is retained)

### System

- system_settings
- document_sequences
- activity_logs

### Transaction / Ledger

- item_ledgers
- customer_ledgers
- vendor_ledgers
- gl_batches
- gl_entries
- adjustments

Exact column names may later be mapped to the existing ERP after the SQL Server backup can be inspected.

## 10. Application Boundaries

Use focused services rather than placing posting logic in controllers.

Expected service boundaries:

- MasterData services
- Authorization/Menu service
- Posting service
- ItemLedger service
- CustomerLedger service
- VendorLedger service
- GL posting service
- Adjustment service
- ActivityLog service
- DocumentSequence service

Controllers remain thin and coordinate validation + service calls.

## 11. Validation and Error Handling

- Use Form Requests for web input validation.
- Wrap posting operations in database transactions.
- Any failed ledger posting rolls back the source posting operation.
- Duplicate document numbers are rejected.
- Reversal cannot be executed twice against the same posted transaction unless explicitly allowed by future module policy.
- Unauthorized menu and action access returns 403.

## 12. Database Integrity Rules

Use SQL Server foreign keys and unique indexes where appropriate.

Mandatory protections:

- posted ledger UPDATE trigger -> reject
- posted ledger DELETE trigger -> reject
- unique document sequence constraints where practical
- reversal reference/index to prevent duplicate reversal

## 13. Seed Data

Baseline seeders should create:

- Administrator role
- Administrator user with development-only initial credentials that must be changed immediately
- Core menu hierarchy
- Core permissions
- Role-menu-permission grants for Administrator
- Default document sequences

## 14. UI Baseline

Main navigation:

- Dashboard
- Master Data
  - Items
  - Customers
  - Vendors
  - Chart of Accounts
  - Warehouses
  - UOM
  - Price Levels
- Transactions
  - Inventory
  - Sales
  - Purchasing
  - Finance
  - Adjustment
- Ledgers
  - Item Ledger
  - Customer Ledger
  - Vendor Ledger
  - General Ledger
- Configuration
  - Users
  - Roles
  - Menu Security
  - Permissions
  - Numbering
  - System Settings
- Audit
  - User Activity Log

The baseline prioritizes functional ERP administration over visual customization.

## 15. Testing Requirements

Minimum automated tests:

- unauthorized menu access is denied
- master create/update works with permission
- user without permission cannot edit
- posting creates expected ledger rows
- posted ledger cannot be updated
- posted ledger cannot be deleted
- reversal creates balancing/opposite ledger entries
- duplicate reversal is rejected
- activity log records sensitive business actions
- document sequence produces unique numbers

Where database triggers cannot be exercised in the default unit-test environment, provide integration SQL scripts and corresponding application-level tests.

## 16. Legacy ERP Mapping Phase

Once the backup is accessible, perform a mapping pass for:

- item master
- customer master
- vendor master
- chart of accounts
- warehouse
- UOM
- price level
- tax/config tables
- document numbering
- current ledger structures
- stored procedures/views/functions relevant to posting

Each discovered structure receives one of these decisions:

- REUSE
- MAP/ADAPT
- REBUILD
- DEPRECATE

The Laravel baseline must remain usable before this mapping is finished.

## 17. Out of Scope for Initial Baseline

The following are not required in the first deliverable unless added later:

- full sales order/invoice workflow
- full purchasing workflow
- inventory costing methods beyond ledger skeleton
- accounts receivable aging engine
- accounts payable aging engine
- bank reconciliation
- fixed assets
- payroll
- manufacturing/MRP
- advanced approval workflow builder
- public API/mobile app
- BI dashboard

The baseline should make these future modules straightforward to add without weakening ledger immutability or security.
