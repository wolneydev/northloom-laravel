# Quickstart: Validate Project Financial Management

## Prerequisites

- PHP 8.3 and Composer dependencies installed.
- PostgreSQL configured for concurrency validation; SQLite may be used for normal tests.
- Application key, migrated database, and Passport keys/client configured.
- An authenticated user. A legacy project without currency is useful for validating the
  configuration guard.

See [the API contract](contracts/openapi.yaml) for complete request and response schemas
and [the data model](data-model.md) for financial invariants.

## Prepare the Application

Using Docker:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan passport:install
```

Or locally:

```bash
composer install
php artisan migrate
php artisan passport:install
php artisan serve
```

Set the bearer token returned by the existing login endpoint:

```bash
export API_URL=http://localhost:8888/api
export TOKEN="<passport-access-token>"
```

## Scenario 1: Create a Project with Currency

```bash
curl -sS -X POST "$API_URL/projects" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Conference","starts_on":"2026-07-26","expected_ends_on":"2026-08-30","currency":"BRL"}'
```

Expected:

- Creation returns HTTP 201.
- The response contains `currency: "BRL"`.
- A missing, malformed, or unsupported currency returns HTTP 422.

Save the project id, create a task in that project through the existing task endpoint,
then export both identifiers:

```bash
export PROJECT_ID="<created-project-id>"
export TASK_ID="<task-id-in-project>"
```

For a legacy project whose currency is `null`, verify that fund and cost creation return
HTTP 422 until the owner configures it:

```bash
curl -sS -X PUT "$API_URL/projects/<legacy-project-id>" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"currency":"BRL"}'
```

## Scenario 2: Create and List a Fund

```bash
curl -sS -X POST "$API_URL/projects/$PROJECT_ID/funds" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Operating budget","opening_balance":"1000.00"}'

curl -sS "$API_URL/projects/$PROJECT_ID/funds" \
  -H "Authorization: Bearer $TOKEN"
```

Expected:

- Creation returns HTTP 201.
- The response identifies the inherited project currency as `"BRL"`.
- Both `opening_balance` and `available_balance` are `"1000.00"`.
- Listing returns the fund in the standard paginated `data`, `links`, and `meta`
  envelope.

Save the returned fund id:

```bash
export FUND_ID="<created-fund-id>"
```

## Scenario 3: Register and List a Cost

```bash
curl -sS -X POST "$API_URL/projects/$PROJECT_ID/costs" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount":"125.50","description":"Venue deposit","incurred_on":"2026-07-26"}'

curl -sS "$API_URL/projects/$PROJECT_ID/costs" \
  -H "Authorization: Bearer $TOKEN"
```

Expected:

- Creation returns HTTP 201 with amount `"125.50"` and currency `"BRL"`.
- The cost appears in the project-scoped paginated list.
- The fund balance remains `"1000.00"` because costs do not debit funds.

## Scenario 4: Allocate Funds to a Task

```bash
curl -sS -X POST "$API_URL/tasks/$TASK_ID/financial-allocations" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"fund_id\":$FUND_ID,\"amount\":\"250.00\"}"

curl -sS "$API_URL/projects/$PROJECT_ID/funds" \
  -H "Authorization: Bearer $TOKEN"
```

Expected:

- Allocation returns HTTP 201 with amount `"250.00"` and currency `"BRL"`.
- Exactly one allocation row exists for the task and fund.
- The fund's `available_balance` is `"750.00"`.

## Scenario 5: Reject Currency Change After Financial Activity

```bash
curl -sS -i -X PUT "$API_URL/projects/$PROJECT_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"currency":"USD"}'
```

Expected:

- Response is HTTP 422 with a `currency` error.
- The project currency remains `"BRL"`.
- Existing funds, costs, allocations, and balances remain unchanged.

## Scenario 6: Reject Insufficient Balance Without Partial Changes

```bash
curl -sS -i -X POST "$API_URL/tasks/$TASK_ID/financial-allocations" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"fund_id\":$FUND_ID,\"amount\":\"800.00\"}"
```

Expected:

- Response is HTTP 422 with an `amount` or balance error.
- The fund remains at `"750.00"`.
- No allocation is created for `"800.00"`.

## Scenario 7: Reject Cross-Project and Cross-Owner Use

Attempt allocation with a fund from another project, then repeat with resources owned
by another user.

Expected:

- A same-owner fund from a different project is rejected with HTTP 422.
- Access to another user's task or fund is rejected with HTTP 403.
- No allocation or balance update persists.

## Automated Validation

Run formatting checks and the full suite:

```bash
vendor/bin/pint --test
php artisan test
```

Run the allocation-focused tests:

```bash
php artisan test --filter=FinancialAllocation
```

The focused suite must cover:

- required, normalized, supported project currency;
- legacy currency configuration and unconfigured-project financial rejection;
- currency visibility in project and financial responses;
- currency immutability after financial activity;
- exact successful debit;
- insufficient balance;
- task/fund project mismatch;
- non-owner access;
- rollback when allocation creation or balance persistence fails;
- two competing allocations that together exceed the starting balance.

The true concurrent allocation test must use PostgreSQL because SQLite does not provide
the same row-level `FOR UPDATE` behavior. It passes only when at most one competing
allocation succeeds and the final balance is non-negative and exact.
