# 04 Calendar tasks

**Status:** Done

## Why

Projects need dated calendar items with time, status, and optional notes.

Expected result: authenticated CRUD on `/api/tasks`, always tied to an owned project.

---

## What

`TaskController` + `TaskService`. `project_id` must exist for the caller. Time-only `starts_at` is combined with `task_date`. List supports `start`/`end`/`status`/`priority`.

The system must:

* Reject tasks on another user’s project (422 `project_id`)
* Scope list/show/update/delete by owner policy

### Expected Behavior

#### POST /api/tasks

Input:

```text
{ "project_id": 1, "title": "Reunião", "task_date": "2026-06-17", "starts_at": "09:00" }
```

Output:

```text
HTTP 201  { "data": { "id": 1, "title": "Reunião", "task_date": "2026-06-17", ... } }
```

---

## Out of Scope

* Telegram delivery (05–06)
* Financial allocations (07)

---

## Context

* `app/Http/Controllers/Api/TaskController.php`
* `app/Http/Requests/Task/StoreTaskRequest.php`
* `tests/Feature/TaskTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Task resource API

**What:** apiResource, filters, Portuguese priority/status aliases.

**Files:** `TaskController.php`, `NormalizesTaskInput.php`, `TaskPolicy.php`

**Verify**

```bash
php artisan test --compact tests/Feature/TaskTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Foreign project | 422 `project_id` | `test_user_cannot_create_task_in_another_users_project` |
| `ends_at` before `starts_at` | 422 `ends_at` | TaskTest |
| Date range list | only in-range rows | `test_user_can_list_tasks_within_a_date_range` |

Follow `app/Http/Requests/Task/StoreTaskRequest.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] `tests/Feature/TaskTest.php` passing.
* [x] Create/list scoped to owner.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Calendar is the `tasks` table (`task_date`), not a third-party calendar.
* **Deviations:** None.
* **Remaining:** None.
