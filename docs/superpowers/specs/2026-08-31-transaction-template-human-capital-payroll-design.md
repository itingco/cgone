# CGOne ERP — Transaction Template & Human Capital / Payroll Design Specification

**Project:** CGOne ERP  
**Design status:** Approved in discussion  
**Design date:** 31.08.2026  
**Database:** PostgreSQL, per active CGOne database  
**Scope:** Business Unit behavior, Transaction Template, Human Capital, Time Management, Full Payroll, Payroll-to-GL

---

## 1. Executive Decisions

The following decisions are fixed:

1. **Business Unit selector is removed from the sidebar.**
2. Business Unit is stored on records and selected/derived per transaction.
3. Business Unit is a normal report dimension/filter/group field.
4. Transaction Template uses **Default Only** behavior; defaults remain editable while the document is OPEN.
5. Human Capital data lives in each active ERP database, not a central shared HR database.
6. CGOne calculates payroll end-to-end.
7. Salary Components are configurable.
8. Tax/statutory calculations are controlled by versioned payroll engines/rules, not arbitrary user formulas.
9. Employee organization and salary setup are effective-dated.
10. Payroll Calculation creates a historical snapshot.
11. **FINALIZED payroll is immutable.**
12. Corrections after finalization use Payroll Adjustment / Off-cycle Payroll.
13. Payroll may be posted to the General Ledger using configured Salary Component posting setup.

---

# PART A — BUSINESS UNIT

## 2. Remove Global Business Unit Selector

The Business Unit selector currently shown in the sidebar must be removed.

The application header continues to select the active **database**.

There is no concept of:

- active BU session;
- global BU context;
- hidden BU filter;
- BU hierarchy in the sidebar.

Business Unit becomes a normal business field stored on records.

---

## 3. Business Unit on Transactions

Business Unit is available on business records that need an organizational dimension, including:

- Sales Request
- Sales Order
- Shipment
- Sales Invoice
- Purchase Request
- Purchase Order
- Receipt
- Purchase Invoice
- Goods Transfer Request
- Goods Transfer
- Inventory Adjustment
- Journal / GL Batch where applicable
- Employee Allocation
- Payroll Result / Payroll Posting

A report may:

- show Business Unit;
- filter Business Unit;
- group by Business Unit;
- compare Business Units.

A report must not automatically filter using a sidebar/session Business Unit.

---

## 4. Business Unit Default Priority

When a transaction is created, default values are resolved in this order:

1. **Source Document**
2. **Transaction Template**
3. Empty/manual selection

Example:

Sales Order BU = MEDAN  
→ Create Shipment  
→ Shipment default BU = MEDAN

The Transaction Template does not overwrite an inherited source-document value.

All defaults remain editable while the document is OPEN, subject to normal user permissions and validation.

When a document becomes RELEASED, transaction business values follow the normal CGOne release locking policy.

---

# PART B — TRANSACTION TEMPLATE

## 5. Module

Menu:

**Configuration → Transaction Templates**

A single reusable template engine supports both Sales and Purchase document families.

Future transaction types may use the same engine.

---

## 6. Template Header

Suggested fields:

- Code
- Name
- Description
- Is Active
- Applies To
- Is Default for selected document type (optional)
- Created By
- Updated By
- Created At
- Updated At

`Applies To` supports:

### Sales
- Sales Request
- Sales Order
- Shipment
- Sales Invoice

### Purchase
- Purchase Request
- Purchase Order
- Receipt
- Purchase Invoice

Potential later use:
- Inventory Adjustment
- Goods Transfer
- Journal

---

## 7. Template Default Values

Initial supported default fields:

### Organization
- Business Unit

### Inventory
- Location
- Bin

### Commercial
- Price Level
- Currency
- Payment Term

### Tax
- Tax Type / Tax Posting Group

### Document
- Number Series
- Default Notes / Remarks

### Future
- Salesperson
- Buyer / Procurement Officer

Every template value is a **default only**.

User may change it while the transaction remains OPEN.

---

## 8. Posting Group Rule

Transaction Template does **not** directly choose arbitrary GL accounts.

Normal operational posting continues to derive accounting from:

- Customer Posting Group
- Vendor Posting Group
- Inventory Posting Group
- General Product Posting Group
- Tax Posting Group
- Posting Setup

This separation is mandatory.

The purpose of Transaction Template is operational input acceleration, not accounting override.

---

## 9. Template Application Audit

A document may store:

- transaction_template_id
- transaction_template_code_snapshot

This provides traceability of which template supplied the initial defaults.

Changing a Transaction Template later does not rewrite existing transactions.

---

# PART C — HUMAN CAPITAL ARCHITECTURE

## 10. Database Placement

Human Capital lives inside each active CGOne ERP database.

Changing database from the header changes the Human Capital dataset as well.

Example:

CGOne ERP Medan:
- Employees
- Attendance
- Leave
- Payroll
- HR configuration

CGOne ERP Jakarta:
- its own Employees
- Attendance
- Leave
- Payroll
- HR configuration

No shared central HR database is required in this design.

---

## 11. Human Capital Menu

Suggested top-level section:

**HUMAN CAPITAL**

Subsections:

### Employee
- Employee Master
- Employee Allocation History
- Employment / Contract History
- Retirement / Termination

### Time Management
- Shifts
- Work Schedules
- Attendance
- Attendance Corrections
- Leave / Permission
- Overtime
- Holiday Calendar

### Payroll
- Payroll Periods
- Salary Components
- Employee Salary Setup
- Other Income / Deduction
- Payroll Calculation
- Payroll Review
- Payroll Finalization
- Payroll Adjustment / Off-cycle
- Payslips
- Payroll Posting to GL

### Organization
- Departments
- Sub Departments
- Positions / Job Titles
- Levels
- Groups
- Workgroups
- Teams
- Office Locations
- Payroll Groups

### Configuration
- Leave Types
- Overtime Types
- Payroll Rules
- Tax Rules
- BPJS / Statutory Rules
- Salary Component Posting Setup

### Reports
Use the existing CGOne Report Center.

---

# PART D — EMPLOYEE & ORGANIZATION

## 12. Employee Master

Suggested master fields include:

### Identity
- Employee Code
- Full Name
- Nick Name
- Birth Place
- Birth Date
- Gender
- Photo

### Employment
- Join Date
- Original Join Date
- Rejoin Date where needed
- Current Employment Status display
- Current Job Status display
- Termination Date display

### Contact
- Work Email
- Personal Email
- Phone
- Address
- Emergency Contact

### Government / Statutory
- KTP
- NPWP
- Tax Registration Date
- BPJS Kesehatan Number
- BPJS Ketenagakerjaan / Jamsostek Number
- Pension Number where applicable

### Bank
- Payment Method
- Primary Bank
- Bank Account Number
- Bank Branch
- Bank Account Owner

Optional multiple payment destinations may be added later.

### Other
- Remarks
- Active flag

Organization assignments do not rely only on static fields on the Employee row.

---

## 13. Organization Masters

Initial masters:

- Business Unit
- Department
- Sub Department
- Position / Job Title
- Level
- Group
- Workgroup
- Team
- Office Location
- Payroll Group
- Reporting Line / Report To

Additional legacy dimensions can be migrated only when they have an actual current business use.

---

## 14. Employee Allocation History

Core table concept:

`employee_allocations`

Each allocation is effective-dated.

Suggested fields:

- employee_id
- effective_from
- effective_to
- business_unit_id
- department_id
- sub_department_id
- position_id / job_title
- level_id
- group_id
- workgroup_id
- team_id
- office_location_id
- payroll_group_id
- report_to_employee_id
- employment_status
- job_status
- contract_no
- contract_expiry_date
- cost_center_id where implemented
- notes
- created_by
- updated_by

Rules:

- no overlapping effective periods for the same employee;
- current allocation = row whose period covers today;
- payroll uses the allocation effective for the payroll period/snapshot;
- historical payroll never derives organization from today's employee allocation.

---

# PART E — TIME MANAGEMENT

## 15. Shift Master

Suggested fields:

- Code
- Name
- Start Time
- End Time
- Break Minutes
- Grace Late Minutes
- Standard Work Minutes
- Overtime Eligible
- Cross Day
- Active

Example:

NORMAL = 08:00–17:00  
SHIFT-1 = 07:00–15:00  
SHIFT-2 = 15:00–23:00

---

## 16. Work Schedule

Supports:

### Pattern
Weekly recurring schedule.

Example:
- Monday NORMAL
- Tuesday NORMAL
- Wednesday NORMAL
- Thursday NORMAL
- Friday NORMAL
- Saturday OFF
- Sunday OFF

### Employee Assignment
Employee receives a schedule pattern for an effective period.

### Date Override
A specific date may override the pattern.

---

## 17. Attendance

Attendance stores actual time data.

Suggested fields:

- Employee
- Work Date
- Shift
- Scheduled In
- Scheduled Out
- Check In
- Check Out
- Late Minutes
- Early Leave Minutes
- Working Minutes
- Overtime Candidate Minutes
- Attendance Status
- Source
- Raw Source Reference
- Notes

Supported sources from the start:

- MANUAL
- IMPORT
- MACHINE
- API

Attendance calculation must preserve raw imported values separately from approved corrections.

---

## 18. Attendance Correction

Workflow:

DRAFT → SUBMITTED → APPROVED / REJECTED

Stores:

- original value
- requested corrected value
- reason
- attachment
- requester
- approver
- approval timestamps

Only approved corrections affect payroll inputs.

---

## 19. Leave / Permission

Leave Type configuration:

- Code
- Name
- Paid / Unpaid
- Deduct Balance
- Payroll Effect
- Requires Attachment
- Active

Leave request:

- Employee
- Leave Type
- Start Date
- End Date
- Total Days / Hours
- Reason
- Attachment
- Status
- Approver
- Approval time

Status:

- DRAFT
- SUBMITTED
- APPROVED
- REJECTED
- CANCELLED

Only approved leave affects payroll.

---

## 20. Overtime

Overtime record/request:

- Employee
- Date
- Start
- End
- Actual Hours
- Approved Hours
- Overtime Type
- Rate Type
- Reason
- Status
- Approver

Only approved overtime feeds payroll.

The payroll design must be capable of reproducing legacy hour buckets such as 150%, 200%, 300%, and 400% where the adopted payroll rule requires them.

---

# PART F — SALARY CONFIGURATION

## 21. Salary Component Master

Initial component types:

- EARNING
- DEDUCTION
- EMPLOYER_CONTRIBUTION
- INFORMATION_ONLY

Suggested fields:

- Code
- Name
- Component Type
- Calculation Method
- Taxable Flag
- BPJS / Statutory treatment
- Prorate flag
- Display on Payslip
- Display Order
- Posting enabled
- Debit Account
- Credit Account
- Active
- Effective From
- Effective To

Example component codes that may be mapped from legacy payroll:

- GAPOK
- TJAB
- TKOM
- TKHU
- TMAK
- TTRA
- OVERTIME
- THR
- BONUS
- TAX
- BPJSKES
- BPJSTK
- LOAN

The migration mapping must use actual legacy meaning rather than assuming code semantics when unclear.

---

## 22. Configurable Formula Engine

Ordinary Salary Components may use a safe formula engine.

Allowed sources may include:

- Fixed Amount
- Basic Salary
- Another Salary Component
- Attendance Present Days
- Working Days
- Overtime Hours
- Late Minutes
- Leave Days
- Percentage
- Constant

Allowed operations:

- +
- -
- *
- /
- MIN
- MAX
- ROUND

No raw PHP.
No arbitrary SQL.

Circular component dependencies are rejected.

Calculation execution order is resolved from component dependencies.

---

## 23. Locked Payroll Engines

The following are not arbitrary user formulas:

- PPh21 / TER statutory engine
- core Take Home Pay finalization rules
- BPJS/statutory engine
- payroll finalization
- rounding policy
- payroll reversal/adjustment behavior

Rules are versioned by effective dates.

Example:

`PAYROLL-LEGACY-V1`

stores the adopted behavior corresponding to the approved migrated legacy payroll baseline.

---

## 24. Employee Salary Setup

Salary setup is effective-dated.

Suggested structure:

`employee_salary_setups`
- employee
- effective_from
- effective_to
- payroll_group
- tax status
- rule version
- notes

`employee_salary_components`
- salary_setup
- salary_component
- fixed amount / rate / override value
- calculation override where explicitly allowed

Rules:

- no overlapping active salary setup periods;
- historical payroll continues to reference snapshot values;
- changing salary September does not change August payroll.

---

# PART G — PAYROLL PERIOD & ENGINE

## 25. Payroll Period

Suggested fields:

- Period Code, e.g. 202608
- Payroll Type
- Payroll Cycle
- Salary Period Start
- Salary Period End
- Attendance Cutoff Start
- Attendance Cutoff End
- Payment Date
- Status
- Payroll Rule Version
- Tax Rule Version
- Notes

Payroll cycle supports:

- Monthly
- Biweekly
- Weekly

---

## 26. Payroll Lifecycle

Proposed lifecycle:

DRAFT  
→ LOADED  
→ CALCULATED  
→ REVIEWED  
→ APPROVED  
→ FINALIZED  
→ POSTED (optional Finance step)

Corrections after FINALIZED do not reopen the original payroll.

---

## 27. Payroll Build Process

Conceptual flow:

1. Create Payroll Period
2. Load eligible employees
3. Resolve effective Employee Allocation
4. Resolve effective Salary Setup
5. Snapshot organization and salary setup
6. Load attendance within cutoff
7. Load approved leave
8. Load approved overtime
9. Load approved one-time income/deductions
10. Execute Salary Components
11. Execute statutory/tax engines
12. Calculate Take Home Pay
13. Save calculation trace
14. Review
15. Approve
16. Finalize
17. Generate Payslip
18. Post to GL if authorized

---

## 28. Payroll Snapshot

Payroll employee result must snapshot at least:

### Employee
- code
- name

### Organization
- Business Unit
- Department
- Sub Department
- Position
- Level
- Group
- Workgroup
- Team
- Office Location
- Payroll Group
- Report To
- Cost Center where applicable

### Salary
- Salary Setup version
- Salary Component input values

### Time
- Attendance totals
- Leave totals
- Overtime totals

### Statutory
- Tax Status
- Tax Rule Version
- BPJS rule/version

### Result
- component calculation outputs
- earnings total
- deduction total
- employer contribution total
- tax
- Take Home Pay

This snapshot is the authoritative historical payroll result.

---

## 29. Payroll Calculation Trace

Every payroll employee calculation should expose a human-readable breakdown.

Example:

GAPOK       5,000,000  
TJAB        1,000,000  
TMAK          500,000  
TTRA          600,000  
OVERTIME      250,000  

Gross        7,350,000  

Late           100,000  
BPJS           150,000  
PPh21          115,000  
Loan           500,000  

Take Home Pay 6,485,000

Tax trace additionally shows:

- Tax Status
- Taxable Base
- Rule Version
- TER / tax bracket used
- Rate
- Tax result
- Rounding

This trace is stored or reproducible from immutable snapshot data.

---

## 30. Legacy Payroll Baseline

Legacy SQL Server HR/payroll artifacts are used as the migration reference.

The design specifically preserves concepts evidenced by the legacy system such as:

- Employee selection
- Payroll Period
- Salary Payment Period / cycle
- Payroll Grade
- Department
- Sub Department
- Workgroup
- Office Location
- Payroll Group
- Status Change snapshot linkage
- Tax Status / Tax Group
- Jamsostek / insurance classifications
- employee banking/payment information
- Take Home Pay
- Overtime records and differentiated overtime hour buckets
- Payroll Start/End and Cutoff Start/End

The legacy schema/script is a source for mapping and parity testing.

CGOne does not copy SQL Server stored procedures directly into PostgreSQL.

Legacy calculations are translated into tested PHP/domain services and PostgreSQL-safe persistence.

---

## 31. Legacy Payroll Parity Testing

Before replacing the legacy calculation for production:

1. select representative historical payroll periods;
2. select representative employees covering different salary/tax/status cases;
3. run legacy payroll result;
4. run CGOne Payroll Rule V1 with equivalent inputs;
5. compare component-by-component;
6. compare Take Home Pay;
7. compare tax;
8. investigate every difference;
9. approve tolerance/rounding policy explicitly.

Target:

`Legacy THP ≈ CGOne THP`

within the approved rounding tolerance.

Parity must cover edge cases such as:

- new joiner
- terminated employee
- unpaid leave
- overtime
- allowance changes
- salary changes
- loan/deduction
- tax status differences
- BPJS/statutory differences
- backdated organization change

---

# PART H — FINALIZATION & IMMUTABILITY

## 32. FINALIZED Payroll Is Immutable

After FINALIZED:

- employee result cannot be edited;
- component result cannot be edited;
- attendance snapshot cannot be replaced;
- organization snapshot cannot be replaced;
- salary setup snapshot cannot be replaced;
- Tax Rule Version cannot be changed;
- Take Home Pay cannot be recalculated in-place.

Database/application protections should mirror CGOne posted-document immutability principles.

---

## 33. Payroll Adjustment / Off-cycle

If an error is found after FINALIZED:

Create a new:

- Payroll Adjustment; or
- Off-cycle Payroll.

It references the original payroll result.

Adjustment contains only the corrective delta.

Example:

Original Finalized THP: 6,500,000  
Missing allowance: +250,000  
Adjustment Payroll: +250,000

The original 6,500,000 result remains unchanged.

---

# PART I — PAYROLL TO GENERAL LEDGER

## 34. Salary Component Posting Setup

Salary Component may define accounting mapping.

Example:

### GAPOK
Debit: Salary Expense  
Credit: Salary Payable

### OVERTIME
Debit: Overtime Expense  
Credit: Salary Payable

### TAX
Credit: PPh21 Payable

### BPJS Employee
Credit: BPJS Payable

### BPJS Company
Debit: BPJS Company Expense  
Credit: BPJS Payable

Mapping must be configured and validated before payroll posting.

---

## 35. Payroll Posting

Payroll posting should aggregate rather than create one GL document per employee.

Typical grouping dimensions:

- Business Unit
- Salary Component / GL Account

Example:

Dr Salary Expense  
Dr Overtime Expense  
Dr Allowance Expense  
Cr PPh21 Payable  
Cr BPJS Payable  
Cr Employee Loan / Receivable  
Cr Salary Payable

GL batch must balance before commit.

Payroll posting uses the standard CGOne immutable GL engine.

---

## 36. Payroll Posting Status

Suggested:

FINALIZED  
→ ready for Finance posting

POSTED  
→ linked to GL batch

If a posted payroll requires accounting reversal, follow CGOne append-only reversal principles.

Do not edit the original payroll or original GL.

---

# PART J — SECURITY

## 37. Security Separation

Employee access and salary access are separated.

Example permissions:

### Employee
- hr.employee.view
- hr.employee.create
- hr.employee.edit
- hr.employee.allocation.view
- hr.employee.allocation.edit

### Time
- hr.attendance.view
- hr.attendance.edit
- hr.attendance.approve
- hr.leave.view
- hr.leave.approve
- hr.overtime.view
- hr.overtime.approve

### Payroll
- payroll.period.view
- payroll.period.create
- payroll.setup.view
- payroll.setup.edit
- payroll.salary.view
- payroll.calculate
- payroll.review
- payroll.approve
- payroll.finalize
- payroll.adjust
- payroll.post
- payroll.payslip.view

A user who can view Employee Master does not automatically receive salary access.

---

# PART K — REPORTING

## 38. Human Capital Standard Reports

Planned reports:

### Employee
- Employee Master
- Headcount
- Employee Allocation History
- Employee Movement
- Join / Termination
- Contract Expiry

### Attendance
- Attendance Detail
- Attendance Summary
- Late Summary
- Missing Attendance
- Attendance Correction History

### Leave
- Leave Balance
- Leave History
- Leave by Type
- Unpaid Leave

### Overtime
- Overtime Detail
- Overtime Summary

### Payroll
- Payroll Summary
- Payroll Detail
- Payroll by Salary Component
- Payroll by Department
- Payroll by Business Unit
- Salary History
- Tax Summary
- BPJS Summary
- Payroll Adjustment History
- Payslip
- Payroll vs GL Reconciliation

These integrate with the existing CGOne Reporting Module.

Business Unit remains a record dimension/filter/group.

---

# PART L — INITIAL DATA MODEL

## 39. Suggested Tables

### Transaction Template
- transaction_templates
- transaction_template_document_types
- transaction_template_defaults

### Employee / Organization
- employees
- departments
- sub_departments
- positions
- levels
- employee_groups
- workgroups
- teams
- office_locations
- payroll_groups
- employee_allocations
- employment_contracts
- employee_retirements

Existing compatible master tables may be reused instead of duplicated after implementation mapping.

### Time
- shifts
- shift_patterns
- shift_pattern_days
- employee_shift_assignments
- work_schedule_overrides
- attendance_records
- attendance_corrections
- leave_types
- leave_balances
- leave_requests
- overtime_types
- overtime_records
- holidays

### Salary
- salary_components
- salary_component_formulas
- employee_salary_setups
- employee_salary_components
- payroll_rule_versions
- tax_rule_versions
- statutory_rule_versions

### Payroll
- payroll_periods
- payroll_runs
- payroll_employees
- payroll_employee_snapshots
- payroll_component_results
- payroll_calculation_traces
- payroll_adjustments
- payroll_adjustment_lines
- payroll_gl_postings

Exact names may be refined during implementation while preserving the design contract.

---

# PART M — IMPLEMENTATION PHASES

## 40. H1 — Core HR + Transaction Template

- remove sidebar Business Unit selector;
- remove active-BU context dependency;
- build Transaction Template;
- add template default resolution;
- Employee Master;
- Organization masters;
- Employee Allocation History;
- HR menu/security;
- migration mapping notes from legacy HR schema.

## 41. H2 — Time Management

- Shift;
- Schedule Pattern;
- Employee Shift Assignment;
- Attendance;
- Attendance Import;
- Attendance Correction;
- Holiday;
- Leave;
- Overtime.

## 42. H3 — Salary Configuration

- Salary Component;
- safe formula engine;
- Employee Salary Setup;
- salary history;
- one-time income/deductions;
- Tax Rule Version foundation;
- BPJS/statutory configuration;
- Salary Component posting setup.

## 43. H4 — Payroll Engine

- Payroll Period;
- employee loading;
- effective allocation snapshot;
- effective salary snapshot;
- attendance/leave/overtime aggregation;
- payroll component calculation;
- legacy Payroll Rule V1;
- tax engine integration;
- calculation trace;
- legacy parity tests.

## 44. H5 — Payroll Workflow

- Payroll Review;
- approval;
- finalization;
- immutability protection;
- Payroll Adjustment / Off-cycle;
- Payslip;
- payroll standard reports.

## 45. H6 — Finance Integration

- payroll posting preview;
- Payroll → GL;
- reversal/adjustment integration;
- Payroll vs GL reconciliation;
- management reports.

---

# PART N — ACCEPTANCE PRINCIPLES

## 46. Functional Acceptance

The subsystem is accepted when:

1. Sidebar no longer has a Business Unit selector.
2. Transaction Business Unit is selected/inherited/defaulted per record.
3. Transaction Template defaults remain editable while OPEN.
4. Template never directly replaces accounting posting-group rules.
5. Human Capital data changes with active ERP database.
6. Employee organization history is effective-dated.
7. Historical payroll uses historical allocation, not current allocation.
8. Salary setup is effective-dated.
9. Attendance, approved Leave, and approved Overtime feed payroll.
10. Configurable Salary Components work without arbitrary PHP/SQL.
11. Payroll statutory/tax engine is versioned.
12. Payroll calculation provides a clear trace.
13. Legacy parity testing is available and discrepancies are auditable.
14. FINALIZED payroll cannot be recalculated or edited.
15. Corrections use Payroll Adjustment / Off-cycle.
16. Payslip derives from immutable payroll result.
17. Payroll can post balanced GL entries using configured Salary Component posting setup.
18. Employee and salary permissions remain separate.
19. Human Capital reports integrate into Report Center.
20. No payroll/report function silently filters using a global Business Unit session.

---

## 47. Non-Goals for the First HR/Payroll Pass

Unless separately approved:

- biometric device vendor-specific integration;
- employee self-service mobile app;
- recruitment/ATS;
- performance appraisal;
- learning management;
- advanced talent management;
- government e-filing API;
- bank host-to-host payroll transfer;
- central HR database spanning multiple CGOne ERP databases.

The data model should leave room for later expansion.
