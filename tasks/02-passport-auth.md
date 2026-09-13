# 02 Passport auth

**Status:** Done

## Why

Protected resources need a bearer token; guests must log in and revoke the token on logout.

Expected result: `POST /api/login` issues a Passport access token; `POST /api/logout` revokes it.

---

## What

Credential check + `createToken('api')`. Guard `auth:api` wraps all private routes.

The system must:

* Reject bad credentials with 422 on `email`
* Return `token_type`, `access_token`, `user`

### Expected Behavior

#### POST /api/login

Input:

```text
{ "email": "ana@example.com", "password": "S3nhaForte!123" }
```

Output:

```text
HTTP 200  { "token_type": "Bearer", "access_token": "...", "user": { ... } }
```

---

## Out of Scope

* User CRUD besides signup
* Social / password-reset flows

---

## Context

* `app/Http/Controllers/Api/AuthController.php`
* `app/Http/Requests/Auth/LoginRequest.php`
* Passport migrations under `database/migrations/*oauth*`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Login and logout

**What:** Issue and revoke Passport tokens.

**Files:** `AuthController.php`, `config/auth.php`

**Verify**

```bash
php artisan test --compact --filter=login
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Wrong password | 422 `email` | login tests |
| Missing token on protected route | 401 | feature tests using Passport |

Follow `app/Http/Controllers/Api/AuthController.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] Auth tests passing.
* [x] Login → token; logout → subsequent 401.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Passport personal access token named `api`.
* **Deviations:** None.
* **Remaining:** None.
