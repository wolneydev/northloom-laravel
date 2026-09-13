# 13 Optional project currency and hours

**Status:** Not started

**Depends on:** `tasks/03-projects.md`, `tasks/07-project-financials.md`

**UI:** Vue app at `/projetos/Vue/northloom` (`ProjectFormPage.vue`). Do not add Blade/session screens in this Laravel repo.

## Why

Create-project currently requires `currency` on `POST /api/projects` and in the Vue form (`Currency *` plus a client check that rejects an empty code). Many projects are planning-only: the owner should save name, dates, and notes without picking a currency yet. Funds/costs already refuse work until currency exists (`ProjectCurrencyNotConfiguredException` / Vue “needs a currency”).

There is also no place to store planned effort on the project. The owner needs an optional hours value per project (not per task).

Expected result: `POST /api/projects` succeeds with no currency; `hours` can be set or omitted; Vue create/edit matches that. Finances still require a configured currency.

---

## What

Make `currency` optional on project create/update (API, MCP, OpenAPI, Vue). Add nullable `hours` on `projects`. Empty string / omitted currency stores `null`. If a currency is sent, keep the existing ISO-3 + `financial.currencies` rules and immutability after funds/costs.

The system must:

* `POST /api/projects` without `currency` (or `currency: null` / `""`) → 201, `data.currency` is `null`.
* `POST /api/projects` with `currency: "ZZZ"` (or any code not in `config('financial.currencies')`) → 422 `currency`.
* `POST /api/projects` with `hours` (e.g. `40` or `12.5`) persists it; omit/`null`/`""` → `hours` is `null`.
* Negative or non-numeric `hours` → 422 `hours`.
* `PUT`/`PATCH` can set or clear `hours`; can set `currency` later; cannot change currency once funds or costs exist (existing `ProjectCurrencyImmutableException`).
* `ProjectResource`, reports MCP payload, and Vue project form/list show `hours`.
* Vue: currency label is not required; do not block submit when currency is empty; send `null` instead of `""`. Add an optional Hours field.
* Creating a fund/cost on a project with `currency = null` still 422 `currency` (existing exception). Do not weaken that.

### Expected Behavior

#### POST /api/projects — no currency, with hours

Input:

```text
Authorization: Bearer {token}
{ "name": "Projeto ERP", "starts_on": "2026-06-16", "expected_ends_on": "2026-07-30", "notes": null, "hours": 40 }
```

Output:

```text
HTTP 201
{ "data": { "id": 1, "name": "Projeto ERP", "currency": null, "hours": "40.00", "starts_on": "2026-06-16", "expected_ends_on": "2026-07-30", "notes": null } }
```

(`hours` JSON may be a number or a decimal string; pick one shape and use it consistently in resource + tests.)

#### POST /api/projects — invalid currency still rejected

Input: same required dates/name plus `"currency": "ZZZ"`.

Output: HTTP 422, `errors.currency`. No row inserted.

#### POST /api/projects/{project}/funds — project without currency

Input: owned project with `currency` null.

Output: HTTP 422, `errors.currency` (existing `ProjectCurrencyNotConfiguredException`). No fund row.

---

## Out of Scope

This task **does not include**:

* Blade/web login or first-party Laravel forms
* Hours on tasks, timesheets, or converting hours to money
* Changing fund/cost/allocation math
* Allowing currency change after funds/costs exist
* Multi-currency conversion
* Making `starts_on` / `expected_ends_on` optional

---

## Context

Relevant files (inspect before implementing):

* `app/Http/Requests/Project/StoreProjectRequest.php` / `UpdateProjectRequest.php` — `currency` is `required` today; empty string is uppercased, not nulled
* `app/Domain/Projects/DTOs/ProjectData.php` — add `hours`; keep `currency` nullable
* `app/Http/Resources/ProjectResource.php` — expose `hours`; `currency` already can be null in DB
* `database/migrations/2026_07_26_000004_add_currency_to_projects_table.php` — column already nullable; this task is validation + hours column
* `app/Models/Project.php` — `$fillable` + casts (`hours` decimal)
* `database/factories/ProjectFactory.php` — factory may keep a default currency; add a `withoutCurrency` state if useful
* `app/Domain/Projects/Services/ProjectService.php` — currency immutability after financial activity
* `app/Mcp/Tools/StoreProjectTool.php` / `UpdateProjectTool.php` — same rules as form requests
* `app/OpenApi/Schemas.php` — drop `currency` from StoreProjectRequest `required`; add `hours`
* `tests/Feature/ProjectTest.php` — `test_project_requires_a_supported_currency` must change: missing currency is valid; `ZZZ` still 422
* `app/Domain/Financials/Services/FundService.php` — leave “no currency → exception” as-is
* Vue: `src/modules/planning/pages/ProjectFormPage.vue`, `src/modules/planning/services/projects.service.js`, `tests/components/ProjectFormPage.spec.js`
* Vue already gates funds when currency is missing (`ProjectFundsPage.vue`); keep that

Task-specific constraints:

* Controllers stay thin; persist only through `ProjectService`.
* `hours`: unsigned decimal, nullable, `min:0` (allow `0`; reject negatives). Precision: two decimal places (e.g. `12.50`).
* Normalize blank `currency` and blank `hours` to `null` in `prepareForValidation` (API + MCP).
* Do not default omitted currency to `BRL`.
* No new Composer/npm packages.

> General project rules (stack, patterns, dependency policy,
> error handling) are in `CLAUDE.md`. Do not repeat them here.

---

## Tasks

### T1: Optional currency on API and MCP

**What:** Store/update accept missing/`null` currency; invalid codes still 422.

**Files:** `StoreProjectRequest.php`, `UpdateProjectRequest.php`, `StoreProjectTool.php`, `UpdateProjectTool.php`, `app/OpenApi/Schemas.php`

**Implementation**

* `currency` → `nullable`, `string`, `size:3`, `Rule::in(config('financial.currencies'))` when present.
* Trim/uppercase; `""` becomes `null` before validation.
* OpenAPI: `currency` not in Store `required`; schema nullable.

**Verify**

```bash
php artisan test --compact tests/Feature/ProjectTest.php
```

### T2: Project hours column and persistence

**What:** Nullable `hours` on `projects`, DTO, resource, factory.

**Files:** new migration, `Project.php`, `ProjectData.php`, `ProjectResource.php`, `ProjectFactory.php`, Eloquent project repository if it maps columns explicitly

**Implementation**

* `hours` `decimal(8, 2)` nullable.
* Cast as decimal/string consistently with other money-like fields in this app (inspect funds `amount`).
* Create/update via `ProjectService` only.

**Verify**

```bash
php artisan test --compact tests/Feature/ProjectTest.php
```

### T3: Vue form

**What:** Optional currency and hours on create/edit; payload sends `null` for blanks.

**Files:** `/projetos/Vue/northloom/src/modules/planning/pages/ProjectFormPage.vue`, `projects.service.js`, `ProjectFormPage.spec.js` (and list/show if they display project fields)

**Implementation**

* Remove `required` and the client regex that blocks empty currency. If the user typed 1–2 letters, still show a field error; empty is OK.
* Hours input (`type="number"` `min="0" `step="0.01"`); omit from required.
* Keep funds page “needs a currency” behavior.

**Verify**

```bash
# from /projetos/Vue/northloom — use the repo’s existing test command
npm test -- --run tests/components/ProjectFormPage.spec.js
```

### T4: Feature tests (API)

**What:** PHPUnit coverage for optional currency, hours, and unchanged financial guard.

**Files:** `tests/Feature/ProjectTest.php`; keep existing fund tests that cover missing currency

**Implementation**

* Create without currency → 201, `currency` null, `assertDatabaseHas` null.
* Create with hours → persisted.
* Create with `ZZZ` → 422.
* Invalid hours → 422; no project.
* Fund store on null-currency project still 422 (reuse FundTest if it already covers this).

**Verify**

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/ProjectTest.php tests/Feature/FundTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| POST without currency | 201, `currency` null | `test_user_can_create_a_project_without_currency` |
| POST currency `ZZZ` | 422 `currency` | `test_project_rejects_unsupported_currency` (rename current require test) |
| POST `hours: -1` | 422 `hours` | `test_project_rejects_negative_hours` |
| POST `hours` omitted | 201, `hours` null | `test_user_can_create_a_project_without_currency` |
| PATCH hours only | 200, hours updated | `test_user_can_update_project_hours` |
| Fund on project with null currency | 422 `currency` | existing FundTest / `ProjectCurrencyNotConfiguredException` |
| Change currency after funds | 422 `currency` | existing ProjectService / ProjectTest immutability |

Follow session/API validation already used on `StoreProjectRequest` (JSON 422 for `/api/*`).

---

## Done when

* [ ] **What** behavior implemented, including the cases in the table above.
* [ ] New/updated tests added; related tests passing.
* [ ] `vendor/bin/pint --dirty --format agent && php artisan test --compact tests/Feature/ProjectTest.php tests/Feature/FundTest.php` runs without errors.
* [ ] Vue form: save a project with empty currency and with hours; funds page still blocks until currency is set.
* [ ] Diff limited to scope; nothing **Out of Scope** was added.

---

## Report

Upon completion, please provide:

* Non-obvious **technical decisions** you made.
* **Deviations** from the spec (or `None.`)
* **Remaining issues** / pending items (or `None.`)
