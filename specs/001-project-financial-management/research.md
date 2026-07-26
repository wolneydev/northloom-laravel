# Research: Project Financial Management

## Financial Module Placement

**Decision**: Add a single `Financials` domain module containing DTOs, repository
contracts, services, financial exceptions/value rules, and the Unit of Work contract.
Keep Eloquent models in `app/Models`, persistence implementations in
`app/Infrastructure/Persistence/Eloquent`, and HTTP concerns in their existing folders.

**Rationale**: This follows the current pragmatic Clean Architecture layout and keeps
the allocation rule independent from controllers and concrete database transactions.

**Alternatives considered**:
- Separate Funds, Costs, and Allocations domain modules: rejected because the entities
  participate in one small financial boundary and would add unnecessary cross-module
  dependencies.
- Put allocation logic in a controller or Eloquent model: rejected because it violates
  the project's thin-controller and service-layer conventions.

## Atomic Unit of Work

**Decision**: Define a domain `UnitOfWorkInterface` with a transaction callback method
and implement it as `EloquentUnitOfWork` using the framework database transaction
facility. `FinancialAllocationService` will execute fund loading, validation, allocation
creation, and balance reduction inside that callback.

**Rationale**: The domain depends on an abstraction while Infrastructure owns the
transaction mechanism. Any exception rolls back both writes, satisfying the all-or-none
allocation invariant.

**Alternatives considered**:
- Call the database transaction facade directly from the service: rejected because the
  Domain layer must not depend on concrete Infrastructure/framework persistence.
- Let each repository open its own transaction: rejected because no repository alone
  owns the full multi-entity operation.

## Concurrent Balance Protection

**Decision**: Load the selected fund with a pessimistic write lock inside the Unit of
Work before checking its balance. Hold that lock until the allocation insert and fund
balance update commit. Production concurrency behavior is validated against PostgreSQL.

**Rationale**: Competing transactions serialize on the fund row, so every balance check
observes the result of the previous committed allocation and overspending cannot occur.

**Alternatives considered**:
- Read then update without locking: rejected because two requests can both pass the
  balance check using the same stale value.
- Optimistic version column with retries: viable but adds retry/version semantics not
  otherwise used by this project.
- Rely only on an application-level pre-check: rejected because it cannot prevent races.

## Exact Monetary Representation

**Decision**: Store amounts as `DECIMAL(15,2)`, expose them as two-decimal strings, and
compare/subtract them through a small exact-money value rule using integer minor units.
Requests accept positive decimal strings with no more than two fractional digits.

**Rationale**: Database decimals preserve exact values, string API values avoid JSON
floating-point drift, and integer minor-unit arithmetic avoids requiring a new money
library or optional PHP extension.

**Alternatives considered**:
- Floating-point columns or arithmetic: rejected because binary floating point cannot
  represent many currency values exactly.
- Store integer cents in the database: exact and viable, but inconsistent with the
  requested available-balance representation and less readable for direct reporting.
- Add a third-party money package: rejected because two-decimal, single-currency amounts
  do not yet justify the dependency.

## Project Currency Configuration

**Decision**: Store one normalized uppercase three-letter currency code on each project.
New projects require a code from an application-maintained list of supported active ISO
4217 currencies. Existing projects may remain temporarily unconfigured after migration,
but fund, cost, and allocation creation are rejected until their owner configures a
currency. Once any financial record exists, the currency is immutable.

**Rationale**: A project-level code gives every amount one unambiguous currency without
duplicating data across funds, costs, and allocations. A temporary unconfigured state
avoids assigning an arbitrary currency to existing projects, while the financial
operation guard prevents ambiguous records. Immutability preserves the meaning of
historical amounts.

**Alternatives considered**:
- Backfill one hard-coded currency for every existing project: rejected because the
  system cannot safely infer the owners' intended currencies.
- Store a currency on every financial row: rejected because the specification forbids
  mixed currencies within a project and duplicated codes could drift.
- Allow currency changes and convert historical amounts: rejected because exchange-rate
  management and conversion are outside scope.
- Add a third-party currency package: rejected because validating against a small
  application-supported ISO 4217 list does not justify another runtime dependency.

## Persistence Model

**Decision**: Add a nullable-during-legacy-transition `currency` code to `projects`, and
create `funds`, `costs`, and `financial_allocations` tables. Funds and costs belong to
projects; allocations belong to both a task and a fund. Foreign keys cascade on parent
deletion. Add indexes for project listings, task allocation history, and fund allocation
history. Add database checks that balances are non-negative and cost/allocation amounts
are positive.

**Rationale**: Foreign keys enforce lifecycle integrity while the service enforces the
cross-entity rule that fund and task belong to the same project.

**Alternatives considered**:
- Duplicate `user_id` on all financial tables: rejected because project ownership is
  already authoritative and duplicated ownership can drift.
- Duplicate `project_id` on allocations: rejected because it is derivable from task and
  fund and still would not by itself enforce equality across both relationships.

## API Shape and Authorization

**Decision**: Use protected nested endpoints:

- `GET|POST /api/projects/{project}/funds`
- `GET|POST /api/projects/{project}/costs`
- `POST /api/tasks/{task}/financial-allocations`

Authorize the parent project or task before service execution. During allocation,
foreign-user funds produce HTTP 403, same-owner funds from another project and
insufficient balances produce HTTP 422, and missing resources produce HTTP 404.

**Rationale**: Parent-scoped routes make ownership and entity relationships explicit,
match existing Passport/Policy behavior, and avoid exposing client-supplied ownership.

**Alternatives considered**:
- Top-level CRUD resources for every entity: rejected because update/delete operations
  are outside the specification and nested routes better express ownership.
- Put `task_id` and `project_id` in allocation payloads: rejected because route context
  is authoritative and prevents relationship tampering.

## Validation and Testing

**Decision**: Use Form Requests that create immutable DTOs. Add feature tests for every
endpoint, validation, ownership isolation, exact balance updates, insufficient balance,
project mismatch, currency creation/configuration/immutability, rejection of financial
operations for unconfigured projects, rollback after an injected repository failure,
and concurrent overspend protection. Unit-test the allocation service and exact-money
rule separately.

**Rationale**: This matches existing validation and Passport feature-test patterns while
covering the financial invariants at both service and database boundaries.

**Alternatives considered**:
- Feature tests only: rejected because rollback failure injection and exact arithmetic
  are clearer and faster at the service/unit level.
- SQLite-only concurrency assertions: rejected because SQLite does not reproduce
  PostgreSQL row-lock semantics; SQLite remains suitable for normal feature tests.
