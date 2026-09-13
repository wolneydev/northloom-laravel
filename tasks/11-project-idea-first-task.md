# 11 Project Idea First

**Status:** Not started

## Why

`POST /api/projects` and `POST /api/tasks` always start from a blank payload. Chat/MCP creation (`StoreProjectTool`, `StoreTaskTool`) does the same: the agent creates immediately when required fields are present. There is no optional, non-persisting prompt based on Brazil-local time and the user’s existing calendar tasks.

The product already has the calendar (`tasks.task_date`), timezone (`config('app.timezone')` → `America/Sao_Paulo`), and a read-only report path (`GET /api/reports` + `GetProjectReportTool`). This task adds a matching read-only suggestion on top of those, without a second create flow.

Expected result: an authenticated API or MCP client can fetch a 1–3 sentence idea (plus a ≤255-character headline), paste it into `name`/`title`/`notes`, then create through the existing store endpoints or tools only after the user accepts or edits the text.

---

## What

On demand, the system builds cycle context from `now()` in `config('app.timezone')` (optional `at` override), maps it to the symbolic success-cycle, optionally refines with **this user’s calendar tasks** via `TaskRepositoryInterface` (not a new calendar package), and returns copy. Nothing is written to `projects`, `tasks`, or notification columns.

The system must:

* Expose a **read-only** authenticated API, modeled on `GET /api/reports`, not a new persist resource.
* Support `target=project` and `target=task` (task requires `project_id` owned by the caller, same `exists` rule as `StoreTaskRequest`).
* Reuse `config('app.timezone')` for Brazil-local time; do not add a user timezone column (`User` has none).
* Use Carbon for weekday / start–end of week and month; use existing task listing/report queries for busy vs empty day and upcoming tasks.
* Return a `headline` (≤255 chars, for `name` or `title`) and a `suggestion` (1–3 sentences, for `notes`).
* Keep generation working when the user has zero tasks and when lunar phase is omitted (`lunar_phase: null`).
* Leave `POST /api/projects`, `POST /api/tasks`, `StoreProjectTool`, and `StoreTaskTool` behavior unchanged except that chat may **call a new read-only tool first** when the user asks for an idea.
* Treat seasons / day–night / moon as a **symbolic creative framework**, never as scientific astrology.

### Expected Behavior

#### GET /api/creation-ideas — project (Sunday night, spring)

Input:

```text
Authorization: Bearer {token}

GET /api/creation-ideas?target=project&at=2026-09-13T21:30:00-03:00
```

Output:

```text
HTTP 200

{
  "data": {
    "target": "project",
    "headline": "Protótipo de uma ideia ainda não explorada",
    "suggestion": "Use este momento para crescimento e imaginação. Crie um projeto em torno de uma ideia que você ainda não testou, com o objetivo de validar uma versão pequena na semana que começa.",
    "field_hints": {
      "headline": "name",
      "suggestion": "notes"
    },
    "context": {
      "country": "BR",
      "timezone": "America/Sao_Paulo",
      "local_date_time": "2026-09-13T21:30:00-03:00",
      "day_period": "night",
      "season": "spring",
      "weekday": "sunday",
      "task_count_on_day": 0,
      "upcoming_task_count": 0,
      "lunar_phase": null,
      "primary_mode": "growth",
      "secondary_mode": "imagination"
    }
  }
}
```

No `projects` row is inserted. The client may then `POST /api/projects` with required `currency`, `starts_on`, `expected_ends_on`, plus edited `name`/`notes`.

#### GET /api/creation-ideas — task on owned project (Wednesday afternoon, summer)

Input:

```text
GET /api/creation-ideas?target=task&project_id=42&at=2026-01-14T15:00:00-03:00
```

Output: HTTP 200, `target: "task"`, `field_hints.headline` = `title`, `field_hints.suggestion` = `notes`, execution-oriented Portuguese copy. `project_id` must pass `Rule::exists('projects', 'id')->where('user_id', $user->id)` like `StoreTaskRequest`. No `tasks` row is inserted; `notify` is not set.

#### GET /api/creation-ideas — calendar refine (busy Friday, autumn)

Input: authenticated user with several `task_date` rows on that Friday; `target=project&at=<that Friday afternoon>`.

Output: HTTP 200 still. `context.task_count_on_day` > 0. Copy may lean toward review/close (autumn + Friday) rather than adding more execution. Missing tasks must not 4xx.

#### MCP SuggestCreationIdeaTool (read-only)

Same inputs/outputs as the API, structured JSON like `GetProjectReportTool`. Must **not** call `ProjectService::create` or `TaskService::create`. Chat agent: if the user asks for an idea before creating, call this tool and show the text; wait for confirmation before `StoreProjectTool` / `StoreTaskTool`.

---

## Out of Scope

This task **does not include**:

* Persisting the suggestion, auto-creating/updating/deleting projects or tasks, or setting `notify` / `notify_at_datetime`
* Telegram or `TaskNotificationService` changes
* A first-party web UI or a second store endpoint
* Generating copy with `HospitableChatAgent` / Ollama (keep templates deterministic for tests)
* New Composer packages (no lunar/calendar library)
* User timezone, chronotype, weather, Northern Hemisphere locales, or extra countries
* Changing required create fields (`currency`, dates, `starts_at`, etc.)
* New `TaskRepositoryInterface` methods unless an existing `paginateForUser` / `reportForUser` call cannot express “count on date” without a huge fetch — prefer `TaskFilters` / `ReportFilters` first

---

## Context

Relevant files (inspect before implementing):

* `config/app.php` — `timezone` already `America/Sao_Paulo`; `locale` is `en` and there are **no** `lang/` files · do not add lang files; put PT-BR template strings in the generator
* `config/financial.php` — pattern for a small dedicated config · add `config/creation_idea.php` only for day-period windows and Southern Hemisphere month→season map
* `app/Http/Controllers/Api/ReportController.php` — thin GET + `{ data: ... }` · copy this HTTP shape
* `app/Http/Requests/Report/ShowReportRequest.php` — query Form Request + `toFilters()`
* `app/Http/Requests/Task/StoreTaskRequest.php` — `project_id` exists-for-owner rule
* `app/Http/Requests/Project/StoreProjectRequest.php` — `name` max 255, `notes` nullable
* `app/Domain/Reports/Services/ReportService.php` — one domain service composing repositories
* `app/Domain/Projects/Services/ProjectService.php` — `final readonly` service; do not fold ideas into `create()`
* `app/Domain/Tasks/Services/TaskService.php` / `TaskRepositoryInterface` — calendar is **tasks**, not a third-party calendar
* `app/Domain/Tasks/DTOs/TaskFilters.php` / `app/Domain/Reports/DTOs/ReportFilters.php` — snake_case DTO properties + `fromArray()`
* `app/Infrastructure/Persistence/Eloquent/EloquentTaskRepository.php` — `whereDate('task_date', …)` already exists
* `app/Http/Resources/ProjectResource.php` — JsonResource wrapping
* `app/OpenApi/Schemas.php` — reusable OA schemas referenced by controllers
* `routes/api.php` — `auth:api` group; `/api` prefix is automatic
* `app/Mcp/Tools/GetProjectReportTool.php` — `#[IsReadOnly(true)]`, Portuguese schema/messages, `AuthenticatesMcpUser`
* `app/Mcp/Tools/StoreProjectTool.php` / `StoreTaskTool.php` — must remain the only writers
* `app/Mcp/Servers/HospitableServer.php` / `routes/ai.php` — register the new tool here
* `app/Ai/Agents/HospitableChatAgent.php` — one instruction: idea tool before store when the user wants a suggestion
* `bootstrap/app.php` — JSON errors for `api/*`; no new exception type needed if validation stays on the Form Request
* `tests/Feature/ReportTest.php` — `Passport::actingAs` + `getJson`
* `tests/Feature/TaskTest.php` — `test_user_cannot_create_task_in_another_users_project` (422 `project_id`)
* `tests/Feature/TaskNotificationTest.php` — `Carbon::setTestNow(...)` for clock
* `tests/Unit/Domain/Projects/ProjectServiceTest.php` — PHPUnit + Mockery, no HTTP
* `database/factories/TaskFactory.php` — `forProject()` for busy-day fixtures
* `app/Providers/AppServiceProvider.php` — bind **interfaces** only; concrete domain services need no binding

Task-specific constraints:

* Follow existing layers: Form Request → thin `final` controller → one `CreationIdeaService` → DTOs. Context / strategy / text are **private collaborators** of that service (same idea as ReportService composing repos), not extra HTTP endpoints.
* PHP DTO public properties stay **snake_case** (`day_period`, not `dayPeriod`).
* Day periods (in `config/creation_idea.php` only): morning `05:00–11:59`, afternoon `12:00–17:59`, night `18:00–04:59` (wraps midnight).
* Southern Hemisphere seasons: summer Dec–Feb, autumn Mar–May, winter Jun–Aug, spring Sep–Nov.
* Calendar refine: `task_count_on_day` = owner’s tasks with that `task_date`; `upcoming_task_count` = tasks from that local date through end of week (reuse `TaskFilters` start/end). Do not load other users’ tasks. Failure to load tasks must not fail generation (treat as zeros).
* Lunar: `null` in v1 (Carbon has no moon phase API in this app).
* Copy: Portuguese string literals (MCP already speaks PT). Tests assert exact strings for frozen clocks.
* Headline must satisfy `max:255` used by store requests.
* Symbolic mapping: morning → planning/priority; afternoon → execution; night → imagination/reflection (not heavy execution). Spring → grow; summer → act; autumn → reflect; winter → plan. Optional lunar unused while null.
* Example pairings: Sunday night + spring → growth idea for the coming week; Friday afternoon + autumn → review and close; Monday morning + winter → week priorities; Wednesday afternoon + summer → highest-impact next action.
* Controllers stay under ~10 lines of logic; OpenAPI attributes on the controller like `ReportController`.
* Tests: `php artisan make:test --phpunit`; Feature uses `RefreshDatabase` (project convention; do not switch to `LazilyRefreshDatabase` unless the suite already does).

> General project rules (stack, patterns, dependency policy,
> error handling) are in `CLAUDE.md`. Do not repeat them here.

---

## Tasks

### T1: Day-period and season config

**What:** Add `config/creation_idea.php` for Brazil-first windows and season months. Read timezone from `config('app.timezone')`.

**Files:** `config/creation_idea.php`

**Implementation**

* `country` => `BR`.
* `day_periods` morning/afternoon/night as in Context.
* `southern_hemisphere_seasons` month integers → `summer|autumn|winter|spring`.
* Comments in the config file (project convention for config).

**Verify**

```bash
php artisan config:show creation_idea
```

### T2: Domain DTOs and CreationIdeaService

**What:** Readonly DTOs + `CreationIdeaService` that loads optional task counts, maps modes, and returns headline + suggestion. No persistence.

**Files:** `app/Domain/CreationIdeas/DTOs/*`, `app/Domain/CreationIdeas/Services/CreationIdeaService.php` (and small mapper/generator classes in the same namespace if kept separate)

**Implementation**

* Input: user id, `target`, optional `project_id`, optional `at`.
* Inject `TaskRepositoryInterface` only (no Eloquent in the domain service).
* Do not call `ProjectService` or `TaskService` write methods.
* `lunar_phase` always `null` in this version.

**Verify**

```bash
php artisan test --compact tests/Unit/Domain/CreationIdeas/CreationIdeaServiceTest.php
```

### T3: GET /api/creation-ideas

**What:** Form Request, controller, resource, OpenAPI schema, route in the `auth:api` group.

**Files:** `routes/api.php`, `app/Http/Controllers/Api/CreationIdeaController.php`, `app/Http/Requests/CreationIdea/ShowCreationIdeaRequest.php`, `app/Http/Resources/CreationIdeaResource.php`, `app/OpenApi/Schemas.php`

**Implementation**

* `GET creation-ideas` next to `GET reports`.
* Query: `target` required `project|task`; `project_id` required_if target=task + owner exists rule; `at` nullable date.
* `authorize(): true` like sibling requests; auth is middleware.
* 200 `{ data: ... }`; 401 without token; 422 on invalid query.

**Verify**

```bash
php artisan route:list --path=creation-ideas --method=GET
php artisan test --compact tests/Feature/CreationIdeaTest.php
```

### T4: Read-only MCP tool and chat instruction

**What:** Mirror the API with `#[IsReadOnly(true)]` / `#[IsIdempotent(true)]`. Register on `HospitableServer`. Point the chat agent at it for “idea first”.

**Files:** `app/Mcp/Tools/SuggestCreationIdeaTool.php`, `app/Mcp/Servers/HospitableServer.php`, `app/Ai/Agents/HospitableChatAgent.php`

**Implementation**

* Same validation messages style as `GetProjectReportTool` (Portuguese).
* Tool description: returns a suggestion only; does not create.
* Agent instructions: when the user wants a suggested project/task idea, call this tool and present headline + suggestion; do not call store tools until they confirm. Do not invent that astrology is scientific.

**Verify**

```bash
php artisan test --compact tests/Feature/CreationIdeaTest.php --filter=mcp
```

If there is no MCP HTTP feature test yet, cover the tool by instantiating `CreationIdeaService` in the unit test and asserting the server `$tools` array contains the class in a small unit/feature assertion.

### T5: Feature tests (API + calendar refine + no writes)

**What:** PHPUnit feature coverage matching `ReportTest` / `TaskTest`.

**Files:** `tests/Feature/CreationIdeaTest.php`

**Implementation**

* Guest → 401.
* Frozen `at` (or `Carbon::setTestNow`) for Sunday-night-spring and Monday-morning-winter project copy.
* Task idea with `Task::factory()->forProject($project)`.
* Foreign `project_id` → 422 `project_id`.
* Busy day: factory tasks on `task_date`; `task_count_on_day` matches; still 200.
* `assertDatabaseCount('projects', …)` / `tasks` unchanged after GET.
* Invalid `target` / missing `project_id` / invalid `at` → 422.

**Verify**

```bash
php artisan test --compact tests/Feature/CreationIdeaTest.php tests/Unit/Domain/CreationIdeas/CreationIdeaServiceTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Missing bearer token | 401 | `test_guest_cannot_request_a_creation_idea` |
| Invalid `target` | 422 on `target` | `test_creation_idea_requires_valid_target` |
| `target=task` without `project_id` | 422 on `project_id` | `test_task_idea_requires_project_id` |
| `project_id` owned by another user | 422 on `project_id` (same as `StoreTaskRequest`) | `test_task_idea_rejects_foreign_project` |
| Invalid `at` | 422 on `at` | `test_creation_idea_rejects_invalid_at` |
| 03:00 local | `day_period=night` | `test_context_detects_night_after_midnight` |
| User has no tasks | 200, counts 0, suggestion still 1–3 sentences | `test_idea_succeeds_when_calendar_is_empty` |
| GET idea | No new project/task rows; `notify` untouched | `test_creation_idea_does_not_persist_resources` |

Follow the existing error pattern in `app/Http/Requests/Task/StoreTaskRequest.php` and `app/Http/Controllers/Api/ReportController.php`.

---

## Done when

* [ ] **What** behavior implemented, including the cases in the table above.
* [ ] New tests added; related tests passing.
* [ ] `vendor/bin/pint --dirty --format agent && php artisan test --compact tests/Feature/CreationIdeaTest.php tests/Unit/Domain/CreationIdeas/CreationIdeaServiceTest.php` runs without errors.
* [ ] Manual flow: `GET /api/creation-ideas?target=project` → 200; then `POST /api/projects` with edited `name`/`notes` plus required fields creates one project; the GET alone creates none.
* [ ] Diff limited to scope; nothing **Out of Scope** was added.

---

## Report

Upon completion, please provide:

* Non-obvious **technical decisions** you made.
* **Deviations** from the spec (or `None.`)
* **Remaining issues** / pending items (or `None.`)
