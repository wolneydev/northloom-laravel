# 06 Task notifications

**Status:** Done

## Why

Due reminders must send once over Telegram, survive restarts, and retry on failure.

Expected result: `tasks:send-notifications` (every minute, `America/Sao_Paulo`) claims due rows and sends.

---

## What

Task fields: `notify`, `notify_at_datetime`, `notification_sent_at`, `attempts`, `next_attempt_at`. `TaskNotificationService` + atomic claim on the repository. Schedule `withoutOverlapping(5)`.

The system must:

* Skip when Telegram is disabled or chat id missing
* Not double-send (`notification_sent_at`)

### Expected Behavior

#### php artisan tasks:send-notifications

Input:

```text
Task with notify=true, notify_at_datetime <= now, notification_sent_at null; user Telegram enabled
```

Output:

```text
Telegram message sent; notification_sent_at stamped; command prints count sent
```

---

## Out of Scope

* Email/SMS channels
* Changing user Telegram prefs (05)

---

## Context

* `app/Console/Commands/SendTaskNotifications.php`
* `app/Domain/Tasks/Services/TaskNotificationService.php`
* `routes/console.php`
* `tests/Feature/TaskNotificationTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Dispatch + retry

**What:** Claim, send, backoff.

**Files:** `TaskNotificationService.php`, `EloquentTaskRepository.php`

**Verify**

```bash
php artisan test --compact tests/Feature/TaskNotificationTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Already sent | skip | TaskNotificationTest |
| Concurrent workers | one claim wins | repository claim tests |
| Send failure | retry scheduled | TaskNotificationTest |

Follow `app/Domain/Tasks/Services/TaskNotificationService.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] Notification tests passing.
* [x] Scheduler + command deliver due reminders once.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Idempotent claim + lease; overdue rows recovered after downtime.
* **Deviations:** None.
* **Remaining:** None.
