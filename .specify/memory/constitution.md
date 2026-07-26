<!--
Sync Impact Report
- Version change: template (unversioned) -> 1.0.0
- Added principles: Owner-Isolated Security; Clean Layer Boundaries; Stable API
  Contracts; Reliable Notifications; Tested Changes
- Added sections: Technical Constraints; Development Workflow
- Removed sections: none
- Templates: ✅ .specify/templates/plan-template.md (compatible);
  ✅ .specify/templates/spec-template.md (compatible);
  ✅ .specify/templates/tasks-template.md (updated for mandatory tests)
- Follow-up TODOs: none
-->
# Hospitable Constitution

## Core Principles

### I. Owner-Isolated Security
Every protected endpoint MUST use Passport bearer authentication. Projects, tasks,
and reports MUST be scoped to the authenticated owner; client payloads MUST NOT set
`user_id`, and non-owner access MUST return HTTP 403. Secrets, including the Telegram
bot token, MUST NOT appear in API responses. These rules prevent cross-account data
access and credential disclosure.

### II. Clean Layer Boundaries
HTTP code MUST handle transport concerns only and delegate business behavior to Domain
services. Domain code MUST NOT depend on HTTP classes or concrete Infrastructure
implementations. Infrastructure MUST implement Domain contracts, with bindings declared
in `AppServiceProvider`. This keeps business rules independent of delivery and storage.

### III. Stable API Contracts
Requests MUST be validated before reaching services, and accepted aliases and time-only
values MUST be normalized consistently. Responses MUST use Laravel API resources and
the documented envelopes, field names, formats, status codes, and pagination behavior.
Contract changes MUST update `documentation.md` in the same change.

### IV. Reliable Notifications
Notification delivery MUST preserve the documented eligibility checks, atomic claim,
retry limit, exponential backoff, and overdue recovery behavior. A task MUST be marked
sent only after successful Telegram delivery. These guarantees prevent duplicate sends
and avoid silently losing reminders during failures or downtime.

### V. Tested Changes
Behavior changes MUST include or update PHPUnit tests covering the affected contract.
Security isolation, project and task flows, Telegram settings, reports, and notification
delivery MUST retain feature-test coverage. `php artisan test` or `composer test` MUST
pass before a change is considered complete.

## Technical Constraints

The application MUST remain compatible with PHP 8.3+, Laravel 13, Passport 13,
PostgreSQL, and PHPUnit 12. SQLite MAY be used for local development and tests. Dates
and datetimes MUST follow the documented formats, with `America/Sao_Paulo` as the
default application and scheduler timezone.

## Development Workflow

Specifications and plans MUST identify affected API contracts, ownership rules, layer
boundaries, and tests. Implementation MUST follow the established request-to-resource
dependency flow. Reviews MUST verify constitution compliance, documentation accuracy,
and a passing test suite; exceptions require written justification in the plan.

## Governance

This constitution governs project changes and supersedes conflicting local practices.
Amendments MUST document their rationale, update affected templates and guidance, and
record a semantic version change: MAJOR for incompatible governance changes, MINOR for
new or materially expanded rules, and PATCH for clarifications. Every specification,
plan, task list, and review MUST check applicable principles. Runtime details remain
defined in `documentation.md`.

**Version**: 1.0.0 | **Ratified**: 2026-07-25 | **Last Amended**: 2026-07-25
