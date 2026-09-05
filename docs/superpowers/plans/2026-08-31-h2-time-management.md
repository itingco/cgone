# H2 Time Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add shift scheduling, attendance, corrections, leave, overtime, and holidays as payroll-ready time inputs.

**Architecture:** Preserve raw attendance separately from approved corrections. Effective schedules determine planned time; approved leave/overtime/corrections determine payroll inputs. All data lives in the active ERP database.

**Tech Stack:** Laravel 12, PostgreSQL, Blade, existing permissions/audit.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints
- Only approved corrections/leave/overtime affect payroll.
- Raw attendance import is never overwritten by correction.
- Sources support MANUAL, IMPORT, MACHINE, API.
- Work schedules support weekly patterns and date overrides.

---

### Task 1: Time schema
**Files:** Create `database/migrations/2026_09_01_000100_create_hr_time_management.php`; tests `tests/Feature/HumanCapital/H2SchemaTest.php`.
**Produces:** `shifts`, `shift_patterns`, `shift_pattern_days`, `employee_shift_assignments`, `work_schedule_overrides`, `attendance_records`, `attendance_corrections`, `holidays`, `leave_types`, `leave_balances`, `leave_requests`, `overtime_types`, `overtime_records`.
- [ ] Write schema assertions and verify RED.
- [ ] Implement exact columns/status/indexes from the approved spec.
- [ ] Verify structural GREEN and lint.

### Task 2: Shift and schedule domain
**Files:** models under `app/Models/HumanCapital/Time/`; `ScheduleResolver.php`; controllers/views/routes; unit tests.
**Interface:** `ScheduleResolver::forEmployeeDate(int $employeeId, date $date): ?ResolvedShift`, priority date override > active employee assignment > none.
- [ ] Test priority, cross-day shift, OFF day.
- [ ] Verify RED.
- [ ] Implement minimal resolver + CRUD.
- [ ] Verify GREEN.

### Task 3: Attendance input/import
**Files:** `AttendanceController.php`, `AttendanceImportService.php`, request, views; tests.
**Contract:** unique logical attendance per employee/work date/source reference; import preserves raw check-in/out; calculated late/early/working minutes are derived against resolved schedule.
- [ ] Write manual/import duplicate and calculation tests.
- [ ] Verify RED.
- [ ] Implement.
- [ ] Verify GREEN.

### Task 4: Attendance correction workflow
**Contract:** DRAFT → SUBMITTED → APPROVED/REJECTED; approved effective values are computed without mutating raw attendance.
- [ ] Test invalid transition and approved corrected value.
- [ ] Verify RED.
- [ ] Implement service/controller/UI/audit.
- [ ] Verify GREEN.

### Task 5: Leave and balance
**Contract:** Leave Type controls paid/unpaid, deduct balance, payroll effect, attachment requirement. Only APPROVED request consumes balance/payroll input.
- [ ] Test balance deduction once, cancellation/rejection behavior, date overlap.
- [ ] Verify RED.
- [ ] Implement.
- [ ] Verify GREEN.

### Task 6: Overtime
**Contract:** Approved hours, type/rate bucket; support representation of 150/200/300/400 percent buckets without hardcoding calculation into attendance.
- [ ] Test approval and bucket capture.
- [ ] Verify RED.
- [ ] Implement.
- [ ] Verify GREEN.

### Task 7: H2 menus/security/audit
- [ ] Seed `hr.shifts`, `hr.schedules`, `hr.attendance`, `hr.attendance-corrections`, `hr.leave`, `hr.overtime`, `hr.holidays`.
- [ ] Add permissions for view/edit/approve using existing permission system.
- [ ] Verify Administrator grants and route contracts.

### Task 8: H2 acceptance
- [ ] Standalone structural tests.
- [ ] PHP/Blade lint.
- [ ] workflow/status scan.
- [ ] no global BU context scan.
- [ ] ZIP integrity/checksum.
