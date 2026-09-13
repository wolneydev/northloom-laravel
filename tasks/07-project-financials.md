# 07 Project financials

**Status:** Done

## Why

Projects need BRL/USD/EUR funds, costs, and allocations onto tasks without overspending or crossing owners.

Expected result: nested fund/cost APIs and `POST /api/tasks/{task}/financial-allocations` with money math and 422/403 domain errors.

---

## What

`FundService`, `CostService`, `FinancialAllocationService`, `Money` VO, unit of work. Currency on project; cannot change after financial activity. Allocation decrements fund balance.

The system must:

* Scope funds/costs to owned projects
* Reject foreign fund, mismatch project, insufficient balance

### Expected Behavior

#### POST /api/tasks/{task}/financial-allocations

Input:

```text
{ "fund_id": 1, "amount": "10.00" }
```

Output:

```text
HTTP 201  { "data": { "id": 1, "task_id": ..., "fund_id": 1, "amount": "10.00" } }
```

---

## Out of Scope

* Reports (08)
* Multi-currency conversion

---

## Context

* `app/Http/Controllers/Api/ProjectFundController.php`, `ProjectCostController.php`, `TaskFinancialAllocationController.php`
* `app/Domain/Financials/`
* `bootstrap/app.php` exception JSON
* `tests/Feature/FundTest.php`, `CostTest.php`, `FinancialAllocationTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Funds, costs, allocations

**What:** Nested routes + domain exceptions + concurrency-safe allocation.

**Files:** `FinancialAllocationService.php`, `EloquentUnitOfWork.php`

**Verify**

```bash
php artisan test --compact tests/Feature/FundTest.php tests/Feature/CostTest.php tests/Feature/FinancialAllocationTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Foreign fund | 403/422 | `test_foreign_resources_are_forbidden...` |
| Insufficient balance | 422 `amount` | FinancialAllocationTest |
| Currency change after activity | 422 `currency` | ProjectServiceTest |

Follow `bootstrap/app.php` financial exception renders.

---

## Done when

* [x] **What** behavior implemented.
* [x] Financial feature tests passing.
* [x] Allocate → balance drops; bad owners rejected.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Domain exceptions mapped in `bootstrap/app.php`; `Money` value object.
* **Deviations:** None.
* **Remaining:** None.
