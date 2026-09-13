# 08 Reports

**Status:** Done

## Why

Users need an aggregated, filtered view of their projects and/or tasks without listing every resource by hand.

Expected result: `GET /api/reports` returns `{ data: { filters, projects?, tasks? } }` for the caller only.

---

## What

`ReportService` + `ReportFilters` (`report_type`, `status`, `start_date`, `end_date`). Read-only.

The system must:

* Include projects, tasks, or both
* Hide other users’ rows

### Expected Behavior

#### GET /api/reports

Input:

```text
GET /api/reports?report_type=tasks&status=completed&start_date=2026-06-12&end_date=2026-06-20
```

Output:

```text
HTTP 200  { "data": { "filters": { ... }, "tasks": [ ... ] } }
```

---

## Out of Scope

* CSV/PDF export
* Writing projects/tasks

---

## Context

* `app/Http/Controllers/Api/ReportController.php`
* `app/Http/Requests/Report/ShowReportRequest.php`
* `tests/Feature/ReportTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Report GET

**What:** Query Form Request + aggregated resources.

**Files:** `ReportController.php`, `ReportService.php`

**Verify**

```bash
php artisan test --compact tests/Feature/ReportTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Invalid `report_type` | 422 | ReportTest |
| Other user’s tasks | omitted | `test_user_can_generate_task_report_with_status_and_date_filters` |

Follow `app/Http/Controllers/Api/ReportController.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] Report tests passing.
* [x] GET reports → filtered owner data.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** GET (no side effects); same filter DTO reused by MCP later.
* **Deviations:** None.
* **Remaining:** None.
