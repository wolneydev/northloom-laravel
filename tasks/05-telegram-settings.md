# 05 Telegram settings

**Status:** Done

## Why

Reminders need a chat id and an enable flag per user; the bot token stays server-side.

Expected result: the caller can get/update `/api/me/telegram` and send a test message.

---

## What

`GET`/`PUT /api/me/telegram`, `POST /api/me/telegram/test`. Fields: `telegram_chat_id`, `telegram_notifications_enabled`.

The system must:

* Never expose the bot token
* Test send uses `TelegramNotificationServiceInterface`

### Expected Behavior

#### PUT /api/me/telegram

Input:

```text
{ "telegram_chat_id": "123456", "telegram_notifications_enabled": true }
```

Output:

```text
HTTP 200  { "data": { "telegram_chat_id": "123456", "telegram_notifications_enabled": true } }
```

---

## Out of Scope

* Scheduled dispatch (06)
* Changing bot credentials from the API

---

## Context

* `app/Http/Controllers/Api/TelegramSettingsController.php`
* `app/Infrastructure/Telegram/TelegramNotificationService.php`
* `tests/Feature/TelegramSettingsTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Preferences + test ping

**What:** User columns + HTTP + outbound Telegram client.

**Files:** `TelegramSettingsController.php`, `UpdateTelegramSettingsRequest.php`

**Verify**

```bash
php artisan test --compact tests/Feature/TelegramSettingsTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Guest | 401 | TelegramSettingsTest |
| Invalid payload | 422 | TelegramSettingsTest |

Follow `app/Http/Requests/User/UpdateTelegramSettingsRequest.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] Telegram settings tests passing.
* [x] GET/PUT/test work for the authenticated user.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Interface bound in `AppServiceProvider` for fakes.
* **Deviations:** None.
* **Remaining:** None.
