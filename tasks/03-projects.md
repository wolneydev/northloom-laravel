# 03 Projects

**Status:** Done

## Why

Planning needs a user-owned project with dates and (later) currency.

Expected result: authenticated CRUD on `/api/projects`, scoped to the owner.

---

## What

`ProjectController` + `ProjectService` + repository + `ProjectPolicy`. Currency required on create (`financial.currencies`); immutable after funds/costs exist.

The system must:

* List/show/update/delete only the caller’s projects
* Validate `expected_ends_on >= starts_on`

### Expected Behavior

#### POST /api/projects

Input:

```text
Authorization: Bearer {token}
{ "name": "Projeto ERP", "currency": "BRL", "starts_on": "2026-06-16", "expected_ends_on": "2026-07-30", "notes": null }
```

Output:

```text
HTTP 201  { "data": { "id": 1, "name": "Projeto ERP", "currency": "BRL", ... } }
```

---

## Out of Scope

* Tasks, funds, costs (04, 07)
* User timezone

---

## Context

* `app/Http/Controllers/Api/ProjectController.php`
* `app/Domain/Projects/Services/ProjectService.php`
* `tests/Feature/ProjectTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Project resource API

**What:** apiResource + policy + DTO.

**Files:** `ProjectController.php`, `StoreProjectRequest.php`, `ProjectPolicy.php`

**Verify**

```bash
php artisan test --compact tests/Feature/ProjectTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| End before start | 422 `expected_ends_on` | `test_create_project_fails_when_end_is_before_start` |
| Invalid currency | 422 `currency` | `test_project_requires_a_supported_currency` |
| Other user’s project | 403 | ProjectTest |

Follow `app/Http/Requests/Project/StoreProjectRequest.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] `tests/Feature/ProjectTest.php` passing.
* [x] Owner CRUD works; strangers 403.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Thin controller; currency immutability in `ProjectService`.
* **Deviations:** None.
* **Remaining:** None.
