# Data Model: Project Financial Management

## Money Rules

- Currency precision is two decimal places.
- API and domain DTO amounts are decimal strings such as `"1250.00"`.
- Persistence uses `DECIMAL(15,2)`; values range from `0.00` to
  `9999999999999.99`.
- Opening and available balances may be zero but never negative.
- Cost and allocation amounts must be at least `0.01`.
- All amounts share the project's configured currency; conversion is out of scope.
- New financial records require a configured project currency.
- Currency codes are normalized uppercase ISO 4217 codes from the supported list.

## Project Currency

Each project defines the single currency shared by all of its financial records.

### Field

| Field | Type | Rules |
|---|---|---|
| `currency` | string(3), nullable only for legacy transition | Required for new projects; uppercase supported ISO 4217 code; required before creating any financial record |

### State Transitions

1. **Legacy unconfigured**: an existing project may have no currency immediately after
   migration and cannot create a fund, cost, or allocation.
2. **Configured**: the owner selects a supported currency; project responses expose it.
3. **Financially active**: after the first fund, cost, or allocation, currency changes
   are rejected permanently.

### Invariants

- A configured project has exactly one currency.
- Funds, costs, and allocations inherit their currency from the project and do not store
  separate currency codes.
- Changing other project attributes does not alter its currency.

## Fund

Represents a named source of money available to one project.

### Fields

| Field | Type | Rules |
|---|---|---|
| `id` | integer | Primary key |
| `project_id` | integer | Required foreign key to `projects`; cascade on delete |
| `name` | string(255) | Required; non-empty |
| `opening_balance` | decimal(15,2) | Required; greater than or equal to zero |
| `available_balance` | decimal(15,2) | Required; initialized from opening balance; never negative |
| `created_at` | datetime | System managed |
| `updated_at` | datetime | System managed |

### Relationships

- Belongs to one `Project`.
- Has many `FinancialAllocation` records.

### Indexes

- Index `project_id` for project-scoped listings.

### Database Constraints

- Check `opening_balance >= 0`.
- Check `available_balance >= 0` as defense in depth against invalid direct writes.

### State Transitions

1. **Created**: `available_balance = opening_balance`.
2. **Allocated**: while locked inside the allocation Unit of Work,
   `available_balance = available_balance - allocation.amount`.
3. No update, replenishment, transfer, or deletion endpoint is included in this feature.

## Cost

Represents an expense recorded for one project. A cost does not debit a fund.

### Fields

| Field | Type | Rules |
|---|---|---|
| `id` | integer | Primary key |
| `project_id` | integer | Required foreign key to `projects`; cascade on delete |
| `amount` | decimal(15,2) | Required; at least `0.01` |
| `description` | string(1000) | Required; non-empty |
| `incurred_on` | date | Required |
| `created_at` | datetime | System managed |
| `updated_at` | datetime | System managed |

### Relationships

- Belongs to one `Project`.

### Indexes

- Composite index `(project_id, incurred_on)` for project cost history ordered by date.

### Database Constraints

- Check `amount > 0`.

### State Transitions

- Created once through the registration endpoint.
- Update and deletion are outside this feature.

## FinancialAllocation

Represents money committed from one fund to one task in the same project.

### Fields

| Field | Type | Rules |
|---|---|---|
| `id` | integer | Primary key |
| `task_id` | integer | Required foreign key to `tasks`; cascade on delete |
| `fund_id` | integer | Required foreign key to `funds`; cascade on delete |
| `amount` | decimal(15,2) | Required; at least `0.01` |
| `allocated_at` | datetime | Required; set by the system on success |
| `created_at` | datetime | System managed |
| `updated_at` | datetime | System managed |

### Relationships

- Belongs to one `Task`.
- Belongs to one `Fund`.
- The task's `project_id` must equal the fund's `project_id`.

### Indexes

- Index `(task_id, allocated_at)` for task allocation history.
- Index `(fund_id, allocated_at)` for fund usage history.

### Database Constraints

- Check `amount > 0`.

### State Transitions

1. **Requested**: task, fund, and positive amount have passed request validation.
2. **Authorized**: caller owns the task and the selected fund.
3. **Validated under lock**: the locked fund belongs to the task's project and its
   available balance is sufficient.
4. **Committed**: allocation row is inserted and fund balance is reduced in the same
   Unit of Work.
5. **Rejected/rolled back**: neither row nor balance change persists when any preceding
   check or write fails.

## Existing Entity Changes

### Project

Add field and relationships:

- `currency`: normalized three-letter supported ISO 4217 code; temporarily nullable only
  for existing projects that have not started financial activity.
- `funds`: has many `Fund`.
- `costs`: has many `Cost`.

Project creation requires `currency`. Project updates may configure a legacy project or
change a configured project only while it has no funds, costs, or allocations.

### ProjectData

- `currency: ?string` — required on project creation; optional on partial update and
  normalized to uppercase before persistence.

### Task

Add relationship:

- `financialAllocations`: has many `FinancialAllocation`.

No new task columns are required.

## Domain Transfer Objects

### FundData

- `project_id: int` — derived from the authorized route project.
- `name: string`
- `opening_balance: string`

`available_balance` is not client writable; the service initializes it from
`opening_balance`.

### CostData

- `project_id: int` — derived from the authorized route project.
- `amount: string`
- `description: string`
- `incurred_on: string`

### FinancialAllocationData

- `task_id: int` — derived from the authorized route task.
- `fund_id: int`
- `amount: string`
- `actor_user_id: int` — derived from the authenticated user for ownership enforcement.

`allocated_at` is set by the service and is not client writable.

## Allocation Invariants

The `FinancialAllocationService` must preserve all of these within one Unit of Work:

1. The authenticated actor owns the task.
2. The authenticated actor owns the fund through its project.
3. The task's project has a configured currency.
4. The locked fund's `project_id` equals the task's `project_id`.
5. The locked fund balance is greater than or equal to the requested amount.
6. The allocation amount and balance arithmetic use exact two-decimal values.
7. Exactly one allocation is created for one successful request.
8. The resulting available balance equals the prior balance minus the amount and is
   never negative.
9. Any exception leaves both tables unchanged.
