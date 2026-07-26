# Implementation Plan: Project Financial Management

**Branch**: `001-project-financial-management` | **Date**: 2026-07-26 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from
`/specs/001-project-financial-management/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Add one configured ISO 4217 currency per project, project funds and costs, and atomic
task-level financial allocation. New projects require a supported currency; legacy
projects must configure one before financial activity, and currency becomes immutable
after the first financial record. The feature extends the existing
Domain/Infrastructure/Http architecture with project currency rules and a `Financials`
domain module, Eloquent repositories, thin controllers, Form Request DTO conversion,
API resources, policies, and protected nested routes. Allocation runs through a domain
Unit of Work abstraction backed by one database transaction. The selected fund row is
locked for update, ownership/project/currency/balance invariants are checked, the
allocation is inserted, and the balance is conditionally decremented before commit; any
failure rolls back all changes.

## Technical Context

**Language/Version**: PHP 8.3+

**Primary Dependencies**: Laravel 13.8+, Laravel Passport 13.7+, Eloquent ORM

**Storage**: PostgreSQL in production/Docker; SQLite for ordinary local and automated
tests; `DECIMAL(15,2)` for money

**Testing**: PHPUnit 12.5 feature and unit tests using `RefreshDatabase` and
`Passport::actingAs`; PostgreSQL integration test for row-lock concurrency

**Target Platform**: Linux-hosted REST API, optionally Docker Compose

**Project Type**: Single Laravel REST API

**Performance Goals**: Financial requests complete within two seconds under normal
load; fund locks remain scoped to the shortest possible transaction

**Constraints**: Passport owner isolation; Domain cannot depend on Http or concrete
Infrastructure; one supported uppercase ISO 4217 currency per project; no financial
activity before currency configuration; no currency changes after financial activity;
exact two-decimal arithmetic; allocation insert and debit must commit or roll back
together; concurrent requests must not overspend; existing JSON envelopes and HTTP
status conventions must remain stable

**Scale/Scope**: One new Project field and updated Project API contract, three new
persisted entities, five protected financial endpoints, three repository contracts,
three financial services, one Unit of Work contract, migrations, factories,
parent-resource authorization, API resources, documentation, and tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Pre-Research Gate

- **Owner-Isolated Security — PASS**: Every endpoint remains under `auth:api`.
  Controllers authorize the route project/task, payloads cannot set ownership, and the
  allocation service verifies ownership of the selected fund.
- **Clean Layer Boundaries — PASS**: HTTP validates and delegates; Domain owns DTOs,
  contracts, services, and financial rules; Infrastructure implements Eloquent
  repositories and transaction handling.
- **Stable API Contracts — PASS**: The OpenAPI contract defines envelopes, decimal
  strings, project currency input/output, pagination, validation responses, and status
  behavior.
- **Reliable Notifications — PASS (not affected)**: No notification schema or delivery
  behavior changes.
- **Tested Changes — PASS**: The plan requires feature, unit, rollback, ownership, and
  PostgreSQL concurrency coverage.
- **Technical Constraints — PASS**: The design stays on the existing PHP/Laravel/
  Passport/PostgreSQL/PHPUnit stack.

### Post-Design Gate

- **PASS**: [research.md](research.md), [data-model.md](data-model.md),
  [contracts/openapi.yaml](contracts/openapi.yaml), and
  [quickstart.md](quickstart.md) preserve every pre-research gate.
- **PASS**: No constitutional exception or complexity waiver is required.

## Project Structure

### Documentation (this feature)

```text
specs/001-project-financial-management/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── openapi.yaml
├── checklists/
│   └── requirements.md
└── tasks.md                 # Created by /speckit-tasks, not this command
```

### Source Code (repository root)

```text
app/
├── Domain/Projects/
│   ├── DTOs/ProjectData.php
│   └── Services/ProjectService.php
├── Domain/Financials/
│   ├── Contracts/UnitOfWorkInterface.php
│   ├── DTOs/{FundData,CostData,FinancialAllocationData}.php
│   ├── Exceptions/
│   ├── Repositories/
│   │   ├── FundRepositoryInterface.php
│   │   ├── CostRepositoryInterface.php
│   │   └── FinancialAllocationRepositoryInterface.php
│   ├── Services/{FundService,CostService,FinancialAllocationService}.php
│   └── ValueObjects/Money.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── ProjectFundController.php
│   │   ├── ProjectCostController.php
│   │   └── TaskFinancialAllocationController.php
│   ├── Requests/Financials/
│   └── Resources/{FundResource,CostResource,FinancialAllocationResource}.php
├── Infrastructure/Persistence/
│   ├── Eloquent/
│   │   ├── EloquentFundRepository.php
│   │   ├── EloquentCostRepository.php
│   │   ├── EloquentFinancialAllocationRepository.php
│   │   └── EloquentUnitOfWork.php
├── Models/{Project,Fund,Cost,FinancialAllocation}.php
└── Providers/AppServiceProvider.php

config/
└── financial.php

database/
├── factories/{ProjectFactory,FundFactory,CostFactory,FinancialAllocationFactory}.php
└── migrations/
    ├── *_add_currency_to_projects_table.php
    ├── *_create_funds_table.php
    ├── *_create_costs_table.php
    └── *_create_financial_allocations_table.php

routes/api.php
bootstrap/app.php
documentation.md

tests/
├── Feature/
│   ├── ProjectTest.php
│   ├── FundTest.php
│   ├── CostTest.php
│   └── FinancialAllocationTest.php
├── Integration/FinancialAllocationConcurrencyTest.php
└── Unit/Domain/Financials/
    ├── FinancialAllocationServiceTest.php
    └── MoneyTest.php
```

**Structure Decision**: Extend the existing single Laravel application and its
Domain/Infrastructure/Http layering. Keep Eloquent entities in `app/Models` to match
current Project and Task conventions; "domain models" for this feature means those
persisted entities plus framework-independent financial rules and DTOs in
`app/Domain/Financials`.

## Allocation Transaction Design

1. `StoreFinancialAllocationRequest` validates `fund_id` and an exact positive amount,
   derives `task_id` and `actor_user_id` from route/auth context, and creates an
   immutable DTO.
2. The controller authorizes the route-bound task and calls
   `FinancialAllocationService::allocate`.
3. The service enters `UnitOfWorkInterface::transaction`.
4. `FundRepositoryInterface::findByIdForUpdate` loads and pessimistically locks the
   fund row with its project ownership data.
5. The service rejects a fund not owned by the actor (403), a same-owner fund outside
   the task's project (422), or an insufficient balance (422).
6. `FinancialAllocationRepositoryInterface::create` inserts the allocation.
7. `FundRepositoryInterface::decreaseAvailableBalance` performs a guarded exact
   decrement (`available_balance >= amount`). A zero-row result raises an insufficient
   balance/concurrency exception.
8. The service returns the refreshed allocation and fund state only after commit. Any
   exception from steps 4–7 escapes the callback and triggers rollback.

The pessimistic lock is the primary PostgreSQL serialization mechanism; the guarded
decrement is defense in depth and prevents a negative balance if locking behavior differs
in a test database.

## Repository and Service Responsibilities

- `ProjectService`: normalize and persist project currency, and reject currency changes
  after any fund, cost, or allocation exists.
- `FundRepositoryInterface`: paginate by project, create with equal opening/available
  balances, lock by id, and guarded balance decrement.
- `CostRepositoryInterface`: paginate by project and create a cost.
- `FinancialAllocationRepositoryInterface`: create an allocation and load response
  relationships.
- `FundService` and `CostService`: project-scoped listing/creation orchestration.
- `FinancialAllocationService`: sole owner of cross-entity allocation invariants and
  Unit of Work orchestration.
- `Money`: validate/canonicalize two-decimal strings and compare integer minor units;
  persistence performs the exact decimal decrement.

## HTTP and Error Mapping

- `StoreProjectRequest` requires a supported currency; `UpdateProjectRequest` accepts
  currency for legacy configuration or pre-financial changes. `ProjectResource` always
  exposes the current currency, which can be `null` only for a legacy project awaiting
  configuration.
- Project currency validation uses the supported uppercase ISO 4217 list in
  `config/financial.php`; request input is normalized before validation.
- `ProjectService` rejects a currency change after any fund, cost, or allocation exists
  with an HTTP 422 field error.
- Fund, cost, and allocation creation reject a legacy project without currency with an
  HTTP 422 field error; read and non-currency project operations remain available.
- Nested project controllers call `Gate::authorize('view', $project)` before listing or
  creating funds/costs.
- The task allocation controller calls `Gate::authorize('view', $task)`.
- Child policies are not added because this feature exposes no standalone fund, cost,
  or allocation routes; parent Project/Task policies and the allocation service enforce
  all ownership boundaries.
- Domain financial exceptions are rendered as API JSON in `bootstrap/app.php`:
  ownership denial → 403; missing/immutable currency, project mismatch, and insufficient
  balance → 422 with field errors.
- Missing route models/funds retain 404 behavior.
- `ProjectResource` exposes the project currency. `FundResource`, `CostResource`, and
  `FinancialAllocationResource` expose the inherited project currency, return money as
  fixed two-decimal strings, and use established date formats.
- Update `documentation.md` with entities, routes, payloads, errors, architecture map,
  and tests in the same implementation change.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No constitution violations require justification.
