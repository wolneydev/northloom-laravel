# 09 MCP tools

**Status:** Done

## Why

The chat agent and local MCP clients need the same domain actions as the HTTP API without duplicating rules.

Expected result: `Mcp::local('hospitable')` exposes store/update/report tools as the service user (`USER_LOGIN`).

---

## What

`HospitableServer` tools: projects, tasks, funds, costs, allocations, Telegram, `GetProjectReportTool` (read-only). Validation mirrors Form Requests; Portuguese messages.

The system must:

* Authenticate via `AuthenticatesMcpUser`
* Not persist on read-only report
* Keep HTTP MCP gated (web token tests)

### Expected Behavior

#### StoreProjectTool

Input:

```text
MCP tool args: name, currency, starts_on, expected_ends_on, notes?
```

Output:

```text
Structured JSON with created project id and fields (same as API create)
```

---

## Out of Scope

* Idea-suggestion tool (11)
* Per-request OAuth for stdio MCP (fixed service user)

---

## Context

* `app/Mcp/Servers/HospitableServer.php`
* `routes/ai.php`
* `app/Mcp/Tools/*`
* `tests/Feature/McpWebAuthTest.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Local server + tools

**What:** Register tools; mirror store/update/report.

**Files:** `HospitableServer.php`, `StoreProjectTool.php`, `GetProjectReportTool.php`

**Verify**

```bash
php artisan test --compact tests/Feature/McpWebAuthTest.php
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| HTTP MCP missing token | 401 | `test_web_mcp_server_rejects_requests_without_a_token` |
| Invalid tool input | validation messages (PT) | tool `validate()` |

Follow `app/Mcp/Tools/GetProjectReportTool.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] MCP auth tests passing.
* [x] Local `hospitable` server lists domain tools.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** Stdio local MCP; tools call the same domain services as HTTP.
* **Deviations:** None.
* **Remaining:** None.
