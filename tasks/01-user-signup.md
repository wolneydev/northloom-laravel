# 01 User signup

**Status:** Done

## Why

The API needed a public way to create accounts before issuing tokens.

Expected result: a guest can register and receive the user payload (no token yet).

---

## What

Public `POST /api/users` creates a user via `UserService`. Index/show/update/delete exist on the controller but are **not** routed.

The system must:

* Validate name, email, password confirmation
* Hash the password; return 201 `User` JSON

### Expected Behavior

#### POST /api/users

Input:

```text
{ "name": "Ana", "email": "ana@example.com", "password": "S3nhaForte!123", "password_confirmation": "S3nhaForte!123" }
```

Output:

```text
HTTP 201  { "data": { "id": 1, "name": "Ana", "email": "ana@example.com", ... } }
```

---

## Out of Scope

* Login/token issue (02)
* Listing or mutating users via API

---

## Context

* `app/Http/Controllers/Api/UserController.php` · `StoreUserRequest` · `UserService`
* `app/Http/Requests/User/StoreUserRequest.php`
* `routes/api.php` — public `POST users`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Sign-up endpoint

**What:** Form request + service + resource.

**Files:** `UserController.php`, `StoreUserRequest.php`, `UserService.php`

**Verify**

```bash
php artisan test --compact --filter=User
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Duplicate email | 422 | signup validation |
| Password mismatch | 422 | signup validation |

Follow `app/Http/Requests/User/StoreUserRequest.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] Tests passing for this area.
* [x] `POST /api/users` → 201 user.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Only `store` is public; other user methods stay unrouted.
* **Deviations:** None.
* **Remaining:** None.
