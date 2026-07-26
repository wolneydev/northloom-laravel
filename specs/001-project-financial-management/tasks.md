# Tasks: Project Currency

**Input**: Updated specification and design documents from
`/specs/001-project-financial-management/`

**Purpose**: Implement only the missing project `currency` field. Existing financial
management behavior is already implemented and is outside this task.

## Phase 1: Project Currency

**Independent Test**: Create a project with a supported ISO 4217 currency, verify it is
persisted and returned by project and financial responses, configure a legacy project,
and confirm currency changes and financial operations are rejected according to the
specified lifecycle.

- [X] T001 [US2] Implement the complete project `currency` field lifecycle: add the supported ISO 4217 list in `config/financial.php` and a legacy-nullable three-character column in `database/migrations/*_add_currency_to_projects_table.php`; update persistence and transport in `app/Models/Project.php`, `database/factories/ProjectFactory.php`, and `app/Domain/Projects/DTOs/ProjectData.php`; require, normalize, validate, expose, and enforce post-financial immutability through `app/Http/Requests/Project/StoreProjectRequest.php`, `app/Http/Requests/Project/UpdateProjectRequest.php`, `app/Http/Resources/ProjectResource.php`, and `app/Domain/Projects/Services/ProjectService.php`; reject fund, cost, and allocation creation for unconfigured legacy projects and expose inherited currency through `app/Domain/Financials/Services`, `app/Http/Resources/FundResource.php`, `app/Http/Resources/CostResource.php`, and `app/Http/Resources/FinancialAllocationResource.php`; map currency domain errors in `bootstrap/app.php`; add lifecycle, ownership, validation, response, and regression coverage in `tests/Feature/ProjectTest.php`, `tests/Feature/FundTest.php`, `tests/Feature/CostTest.php`, `tests/Feature/FinancialAllocationTest.php`, and `tests/Unit/Domain/Projects/ProjectServiceTest.php`; then synchronize `documentation.md` and `docs/ARQUITETURA.md` and run the complete test suite

## Dependencies

- T001 has no pending feature-task dependency because funds, costs, and allocations
  already exist.

## Done When

- The single task passes all currency scenarios in
  `specs/001-project-financial-management/quickstart.md`.
- Existing financial behavior remains covered and unchanged except for the required
  project-currency guards and response field.
