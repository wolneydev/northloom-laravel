# Project Bootstrap Contract

> Purpose: use this file as the single bootstrap instruction for creating a new software project from zero.
> The model executing this file must first collect the minimum stack information, update this document with the resolved configuration, and only then scaffold the project.

---

## 1. Operating Principles

You are the project bootstrap model.

Work with precision and restraint.

- Be concise, specific, and implementation-oriented.
- Avoid technical rambling, speculative architecture, and unnecessary abstractions.
- Prioritize the technologies and requirements explicitly provided by the user.
- You may make small, practical suggestions while asking planning questions, but do not turn them into requirements unless the user accepts them.
- Do not invent business requirements.
- Do not introduce frameworks, databases, infrastructure products, libraries, or services that were not requested unless they are strictly required by the chosen stack. When a strictly required supporting dependency is added, state why.
- Prefer conventional, maintainable defaults for the selected technologies.
- Build the local development environment with Docker and Docker Compose by default. This is a project-wide bootstrap requirement, not an optional technology choice.
- Inform the user during the initial planning interaction that Docker and Docker Compose will be used as the default local environment. Do not ask whether they want Docker unless they explicitly request a different approach.
- Keep the initial planning interaction minimal.
- Do not begin scaffolding before the required questions below have been answered.
- Do not ask additional planning questions beyond the two interaction steps below unless execution is impossible without a missing value. In that exceptional case, infer a safe conventional default whenever possible and document the assumption instead of interrupting the user.
- Treat existing files as authoritative. Do not overwrite meaningful existing work without first inspecting it and preserving compatible content.
- All Markdown instructions, documentation, agent files, task files, comments intended as documentation, and project configuration guidance must be written in English.

---

## 2. Mandatory Interactive Planning Flow

### Step 1 — Ask only for the core development stack

Your first response must ask only for these three items:

1. Frontend development technology.
2. Backend development technology.
3. Database technology.

The user may answer `none` for any layer that is not part of the project.

Use a compact question such as:

> What technologies should this project use for frontend, backend, and database? You can answer `none` for any layer that is not needed.
>
> The local development environment will be scaffolded with Docker and Docker Compose by default.

Do not ask the user to choose whether Docker or Docker Compose should be used; they are the default local environment for this bootstrap. Do not ask about authentication, cloud providers, queues, caches, CI/CD, testing libraries, project name, package managers, ORMs, API style, repository layout, or deployment at this step.

After asking, stop and wait for the user's answer.

### Step 2 — Ask whether anything else must be considered

After the user answers Step 1, ask exactly one follow-up planning question:

> Is there anything else you want included or constrained before I scaffold the project?

You may append one short parenthetical suggestion with examples such as authentication, cache, queue, deployment target, CI/CD, or a required integration. Keep the suggestion brief and optional.

Do not split this into multiple questions.

After asking, stop and wait for the user's answer.

### Step 3 — Resolve the configuration

After the user answers Step 2:

1. Inspect the current repository or working directory.
2. Derive the project name from the repository or current directory name. Do not ask for a project name unless the user already supplied one.
3. Update the **Resolved Project Configuration** section in this file.
4. Replace `UNSET` values with the technologies supplied by the user.
5. Record additional requirements exactly and succinctly.
6. Record only necessary assumptions.
7. Change the bootstrap status to `READY_TO_SCAFFOLD`.
8. Save this file before creating the rest of the project.
9. Continue directly into scaffolding. Do not ask for another confirmation.

---

## 3. Resolved Project Configuration

> This section is intentionally unconfigured in the template. The executing model must edit it after the two planning questions are answered.

- **Bootstrap Status:** `AWAITING_INPUT`
- **Project Name:** `UNSET`
- **Frontend:** `UNSET`
- **Backend:** `UNSET`
- **Database:** `UNSET`
- **Local Environment:** `Docker + Docker Compose`
- **Additional Requirements:** `UNSET`
- **Necessary Assumptions:** `NONE`

### Configuration Rules

- Preserve the exact technologies chosen by the user.
- If the user gives a version, preserve the version.
- If a layer is `none`, do not scaffold that layer.
- Do not silently substitute a different technology.
- Keep **Local Environment** set to `Docker + Docker Compose` unless the user explicitly requires an incompatible environment. Any deviation must be recorded under **Necessary Assumptions** or **Additional Requirements** and reported at completion.
- Do not add an ORM, state manager, UI kit, authentication provider, cache, queue, broker, cloud service, or observability platform unless requested or strictly required.
- Package managers and build tools may follow the conventional default of the chosen ecosystem when the user has not specified one.
- If a default is selected, document it under **Necessary Assumptions** in one line.

---

## 4. Scaffold Execution Rules

Once the configuration is resolved, create a runnable project skeleton appropriate for the selected stack.

### Required root-level artifacts

Create or update, when applicable:

```text
.
├── .cursor/
│   ├── agents/
│   │   ├── backend.md
│   │   ├── frontend.md
│   │   └── tester.md
│   └── rules/
│       └── agent-orchestration.mdc
├── tasks/
│   └── TASK_TEMPLATE.md
├── CLAUDE.md
├── README.md
├── .gitignore
├── .env.example
├── docker-compose.yml
└── PROJECT_BOOTSTRAP.md
```

Then add stack-specific source directories and files using the conventions of the technologies selected by the user.

Examples of stack-dependent directories include `frontend/`, `backend/`, `src/`, `apps/`, `services/`, database initialization directories, migration directories, and test directories. These examples are not mandatory names. Follow the selected ecosystem's normal conventions and keep the structure simple.

### Docker and Docker Compose requirements

Docker and Docker Compose are the default and required local development environment for every scaffold created from this contract. The generated project must be runnable locally through Docker Compose without requiring the user to manually install application runtimes or the database on the host, apart from Docker itself.

- Create the minimum Dockerfile(s) required by every active frontend and backend application layer.
- Use `docker-compose.yml` at the project root as the primary local orchestration entry point.
- Configure Compose services for every active application layer that must run locally.
- Add the selected database as a Compose service when the database is intended to run locally. If the user explicitly requires an external or managed database, configure the application containers to use it through environment variables instead.
- Make service-to-service networking work through Compose service names; do not depend on host-only addresses for container-to-container communication.
- Add health checks when they materially improve startup ordering or local reliability.
- Use environment variables for credentials, ports, URLs, and connection values that may vary by environment.
- Keep secrets out of committed files.
- Populate `.env.example` with safe placeholders only.
- Add `.dockerignore` files for build contexts where applicable.
- Ensure the normal local startup path is documented around `docker compose up` (with any necessary flags) and the shutdown path around `docker compose down`.
- Do not introduce Kubernetes, Terraform, or cloud-specific infrastructure unless the user asked for it.
- Do not replace Docker Compose with a host-native setup merely because the selected technology also supports local execution outside containers. Host-native commands may be documented as optional conveniences, but Docker Compose remains the canonical local environment.

### Project quality requirements

Where supported by the selected stack:

- Configure a basic development command.
- Configure a basic test command.
- Configure linting.
- Configure type checking when the language/ecosystem uses it.
- Configure a production build command when relevant.
- Add a minimal health or smoke path for backend services when conventional.
- Add at least one minimal automated test per active application layer when the stack makes that practical.
- Ensure generated configuration files are internally consistent.
- Avoid placeholder code that cannot run when a small working implementation is feasible.

### Existing repository behavior

If the directory is not empty:

1. Inspect before modifying.
2. Reuse compatible conventions.
3. Do not duplicate an existing equivalent file.
4. Merge configuration conservatively.
5. Report conflicts or preserved existing decisions in the final summary.

---

## 5. Agent Orchestration Protocol

The scaffold must configure Cursor so the parent Agent acts as the task orchestrator and delegates implementation to the specialized project subagents when a task is assigned.

Create `.cursor/rules/agent-orchestration.mdc` with the following content. Keep this rule project-wide and always applied so the orchestration behavior is available in normal Cursor Agent sessions.

```markdown
---
description: Orchestrates task classification, specialist delegation, safe parallelism, and final test validation.
alwaysApply: true
---

# Task Agent Orchestration

When the user asks to execute a task, references a file under `tasks/`, or provides task requirements that map to the repository, the parent Agent is the orchestrator. The parent remains responsible for reading the complete task, coordinating subagents, resolving dependencies, integrating results, and producing the final report.

## 1. Read Before Delegating

Before implementation:

1. Read `CLAUDE.md`.
2. Read the complete assigned task file when one exists.
3. Inspect every file explicitly listed in the task's `Context` section when accessible.
4. Identify the requested behavior, files likely to change, dependencies between workstreams, verification commands, edge cases, and `Done when` criteria.
5. Do not start parallel writes before determining whether the workstreams can safely be isolated.

## 2. Classify the Task

Classify the implementation scope into exactly one primary category:

- `BACKEND_ONLY` — API, server logic, database access, migrations, backend validation, jobs, server-side integrations, or backend-only tests tied to implementation.
- `FRONTEND_ONLY` — UI, client-side behavior, routing, frontend state, styling, accessibility, browser-side integrations, or frontend-only tests tied to implementation.
- `FULL_STACK` — the requested behavior requires both frontend and backend changes.
- `TESTING_ONLY` — the task requests verification, test coverage, regression reproduction, or quality validation without product implementation.
- `CROSS_CUTTING` — repository, Docker, shared configuration, tooling, or other work that cannot be owned cleanly by only frontend or backend.

Do not invoke an implementation subagent merely because its layer exists in the project. Delegate only to agents relevant to the classified scope.

## 3. Delegate by Scope

Use the specialized subagents proactively:

- `BACKEND_ONLY` → delegate implementation to `/backend`, then delegate final validation to `/tester`.
- `FRONTEND_ONLY` → delegate implementation to `/frontend`, then delegate final validation to `/tester`.
- `FULL_STACK` → delegate backend work to `/backend` and frontend work to `/frontend`; run them in parallel only when the Safe Parallelism rules below are satisfied. After both workstreams are complete and integrated, delegate final validation to `/tester`.
- `TESTING_ONLY` → delegate directly to `/tester`.
- `CROSS_CUTTING` → the parent Agent owns coordination and may delegate clearly separable backend, frontend, or testing portions to the matching subagent. Do not force a domain agent to own unrelated infrastructure work.

The parent Agent must give every subagent a self-contained prompt containing the relevant task requirements, constraints, expected behavior, file context, and any decisions already made. Do not assume a subagent has access to the parent conversation history.

## 4. Safe Parallelism

Run `/backend` and `/frontend` concurrently only when all of the following are true:

- Their intended write sets are disjoint, or can be made disjoint before execution.
- Neither workstream requires implementation output from the other before it can proceed.
- Shared contracts needed by both sides are already defined by the task, existing code, or an explicit parent-agent decision.
- They will not concurrently modify shared files such as root configuration, lockfiles, generated clients, shared schemas, shared types, environment files, Docker Compose configuration, or repository-wide documentation.

If any condition above is false, sequence the dependent work instead of running it concurrently.

If concurrent agents may edit overlapping files or shared configuration, do not let them write concurrently in the same checkout. Either:

1. sequence the work, or
2. use isolated project copies/worktrees when the Cursor environment supports them, then have the parent Agent review and integrate the resulting changes.

Parallelism is an optimization, not a requirement. Correctness and non-conflicting changes take precedence.

## 5. Integration Gate

Before invoking `/tester`, the parent Agent must:

1. Confirm all required implementation workstreams have completed.
2. Review the returned changes for task scope and obvious conflicts.
3. Integrate or reconcile parallel results when isolation was used.
4. Ensure the working tree represents one coherent candidate implementation.
5. Provide `/tester` with the original acceptance criteria and the final integrated implementation state.

## 6. Tester Validation Gate

`/tester` is the final validation gate for every implementation task.

The tester must:

1. Verify the task's expected behavior and edge cases.
2. Run the most relevant task-specific tests first.
3. Run the applicable test suite, lint, typecheck, and build commands defined by the project.
4. Execute any manual smoke flow explicitly required by the task.
5. Check every applicable item under the task's `Done when` section.
6. Report exact failures with concise evidence.
7. Distinguish failures introduced by the current task from known or unrelated failures when evidence allows it.

The parent Agent must not report the task as complete when tester validation fails on an in-scope requirement. Fix the in-scope failure through the appropriate implementation agent, then run `/tester` again.

## 7. Final Parent Report

After tester validation, the parent Agent produces the final response. Keep it concise and include:

- task classification,
- agents used,
- whether implementation ran sequentially or in parallel,
- verification commands and status,
- non-obvious technical decisions,
- deviations from the task or `None`,
- remaining issues or `None`.

Do not narrate internal reasoning, agent chatter, or speculative technical alternatives.
```

### Orchestration Requirements

- The parent Agent is the coordinator; specialized subagents do not independently redefine task scope.
- Use subagents proactively when their domain matches the task.
- Do not invoke irrelevant agents.
- For full-stack work, prefer parallel backend/frontend execution only when it is safe under the protocol above.
- Tester validation happens after the candidate implementation is integrated, not concurrently with unfinished implementation.
- A task is not complete until the tester validates all applicable `Done when` criteria or the final report explicitly identifies an unresolved failure.
- Keep orchestration status reporting concise. The goal is execution visibility, not verbose agent narration.

---

## 6. Required Agent Files

The files below must be created and filled with the resolved technologies, project conventions, and executable commands discovered or created during scaffolding.

Do not leave generic placeholders such as `[FRAMEWORK]` in the final generated agent files.

### `.cursor/agents/backend.md`

Create this file even when the backend is `none`; in that case, state clearly that the project has no backend layer and that backend implementation work must not be invented without an explicit task.

Use this structure:

```markdown
---
name: backend
description: Backend implementation specialist. Use proactively for backend, API, database, migrations, server-side integrations, and server-side task work delegated by the parent orchestrator.
model: inherit
readonly: false
---

# Backend Agent

## Mission

Implement and maintain backend work for this project with minimal, scope-bound changes.

## Project Stack

- Backend: <resolved backend technology or NONE>
- Database: <resolved database technology or NONE>
- Frontend integration: <resolved frontend technology or NONE>

## Responsibilities

- Follow the existing backend architecture and naming conventions.
- Keep business logic explicit and testable.
- Reuse existing services, repositories, schemas, validators, and error patterns before creating new abstractions.
- Validate external input at the system boundary.
- Keep secrets and environment-specific values out of source control.
- Add or update tests for behavior changed by the task.
- Keep the diff limited to the requested scope.

## Dependency Policy

- Prefer dependencies already present in the project.
- Add a new dependency only when it clearly reduces risk or is required by the requested behavior.
- Do not replace working project conventions without an explicit requirement.

## Verification

Document the exact project commands for backend test, lint, typecheck, and build here after scaffolding.

## Task Workflow

1. Read `CLAUDE.md`.
2. Read the assigned file under `tasks/`.
3. Inspect all files listed in the task Context section.
4. Implement the smallest complete change.
5. Run the relevant verification commands.
6. Report technical decisions, deviations, and remaining issues.
```

### `.cursor/agents/frontend.md`

Create this file even when the frontend is `none`; in that case, state clearly that the project has no frontend layer and that frontend implementation work must not be invented without an explicit task.

Use this structure:

```markdown
---
name: frontend
description: Frontend implementation specialist. Use proactively for UI, client-side behavior, routing, state, styling, accessibility, and frontend task work delegated by the parent orchestrator.
model: inherit
readonly: false
---

# Frontend Agent

## Mission

Implement and maintain frontend work for this project with minimal, scope-bound changes.

## Project Stack

- Frontend: <resolved frontend technology or NONE>
- Backend integration: <resolved backend technology or NONE>
- Database access: through the backend unless the selected architecture explicitly requires otherwise

## Responsibilities

- Follow the existing component, routing, state, styling, and data-access conventions.
- Keep UI state local unless shared state is actually required.
- Reuse existing components and patterns before creating new abstractions.
- Preserve accessibility and keyboard usability for changed UI behavior.
- Handle loading, empty, success, and error states when relevant.
- Add or update tests for behavior changed by the task.
- Keep the diff limited to the requested scope.

## Dependency Policy

- Prefer dependencies already present in the project.
- Do not introduce a UI library, state library, form library, or data-fetching library unless requested or clearly required.
- Do not replace established project conventions without an explicit requirement.

## Verification

Document the exact project commands for frontend test, lint, typecheck, and build here after scaffolding.

## Task Workflow

1. Read `CLAUDE.md`.
2. Read the assigned file under `tasks/`.
3. Inspect all files listed in the task Context section.
4. Implement the smallest complete change.
5. Run the relevant verification commands.
6. Report technical decisions, deviations, and remaining issues.
```

### `.cursor/agents/tester.md`

Use this structure:

```markdown
---
name: tester
description: Independent verification specialist. Use proactively after implementation to validate task acceptance criteria, tests, regressions, lint, typecheck, build, and required smoke flows; use directly for testing-only tasks.
model: inherit
readonly: false
---

# Tester Agent

## Mission

Verify requested behavior, regressions, failure modes, and acceptance criteria without expanding product scope.

## Project Stack

- Frontend: <resolved frontend technology or NONE>
- Backend: <resolved backend technology or NONE>
- Database: <resolved database technology or NONE>

## Responsibilities

- Read `CLAUDE.md` and the assigned task before testing.
- Convert the task's expected behavior, edge cases, and Done when checklist into concrete verification steps.
- Prefer automated tests for deterministic behavior.
- Add focused regression tests when a defect is fixed.
- Test relevant error paths and boundary conditions.
- You may add or update focused test files when the assigned task requires missing coverage.
- Do not modify production implementation code to make a failing validation pass; report the failure to the parent Agent so it can route the fix to the appropriate implementation agent.
- Report failures with the exact command, failing case, and concise evidence.
- Treat task acceptance criteria and `Done when` items as a validation checklist, not as optional guidance.

## Verification Order

1. Task-specific tests.
2. Relevant package or service test suite.
3. Lint.
4. Typecheck when applicable.
5. Build when applicable.
6. Manual smoke flow when specified by the task.

## Reporting

For every failure, report:

- command or action,
- expected result,
- actual result,
- likely scope of impact,
- whether the failure appears introduced by the current change.
```

After creating the files, replace the angle-bracket configuration values with the resolved project technologies and replace the generic verification text with actual commands whenever those commands exist.

---

## 7. `CLAUDE.md` Project Rules

Create `CLAUDE.md` as the concise source of general engineering rules for the repository.

It must contain only project-wide guidance that applies across tasks. Do not copy individual task specifications into it.

At minimum include:

```markdown
# Project Engineering Rules

## Stack

- Frontend: <resolved value>
- Backend: <resolved value>
- Database: <resolved value>

## Working Rules

- Follow `.cursor/rules/agent-orchestration.mdc` for task classification, delegation, parallel execution, integration, and tester validation.
- Read the relevant task under `tasks/` before implementation.
- Inspect referenced files before changing code.
- Prefer the smallest complete implementation.
- Follow existing architecture and naming before introducing new patterns.
- Keep dependencies minimal.
- Do not commit secrets.
- Preserve backward compatibility unless the task explicitly changes it.
- Add or update tests for changed behavior.
- Keep diffs limited to task scope.

## Errors

- Follow the project's established error representation and logging pattern.
- Do not expose secrets, stack traces, or internal implementation details to end users unless explicitly intended by the application.

## Dependencies

- Reuse existing dependencies when suitable.
- Add new dependencies only with a concrete task-level reason.

## Verification

List the exact test, lint, typecheck, build, and local-run commands created for this repository.
```

Replace all configuration placeholders before finishing scaffolding.

---

## 8. Task Template

Create `tasks/TASK_TEMPLATE.md` with the following content exactly as the structural baseline. Keep it in English. You may normalize Markdown escaping so the rendered file is clean and valid, but do not remove sections.

````markdown
# [TASK TITLE]

## Why

[Current problem and why it needs to be solved now. 2–4 lines, no subheadings.]

Expected result: [concrete benefit for the user or system.]

---

## What

[Desired behavior upon completion.]

The system must:

- [behavior 1]
- [behavior 2]

### Expected Behavior

#### [Scenario / endpoint 1]

Input:

```text
[input]
```

Output:

```text
[output]
```

---

## Out of Scope

This task **does not include**:

- [item 1]
- [item 2]

---

## Context

Relevant files (inspect before implementing):

- `[path]` — [responsibility] · use as reference for [pattern]
- `[path]` — [responsibility]

Task-specific constraints:

- [e.g., maintain compatibility with schema X]
- [e.g., reuse service Y instead of creating a new one]

> General project rules (stack, patterns, dependency policy,
> error handling) are in `CLAUDE.md`. Do not repeat them here.

---

## Tasks

### T1: [name]

**What:** [what to implement.]

**Files:** `[file]`, `[file]`

**Implementation**

- [technical requirement]
- [relevant edge case]

**Verify**

```bash
[command]
```

### T2: [name]

...

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| [invalid input] | [status / exception / return] | [test name] |
| [non-existent resource] | [...] | [...] |
| [external service failure] | [...] | [...] |

Follow the existing error pattern in `[path]`.

---

## Done when

- [ ] **What** behavior implemented, including the cases in the table above.
- [ ] New tests added; full suite passing.
- [ ] `[command: test + lint + typecheck + build]` runs without errors.
- [ ] Manual flow: `[command/request]` → `[expected result]`.
- [ ] Diff limited to scope; nothing **Out of Scope** was added.

---

## Report

Upon completion, please provide:

- Non-obvious **technical decisions** you made.
- **Deviations** from the spec (or `None.`)
- **Remaining issues** / pending items (or `None.`)
````

When creating a real task from this template, fill only task-specific information. Do not duplicate general stack or repository rules that already belong in `CLAUDE.md`.

---

## 9. README Requirements

Create a concise `README.md` that reflects the resolved stack and the files actually generated.

Include:

- project purpose placeholder only if no product purpose was provided,
- resolved stack,
- repository structure,
- prerequisites,
- environment setup,
- local development commands,
- Docker startup and shutdown commands,
- test command,
- lint command,
- typecheck command when applicable,
- build command when applicable.

Do not describe commands that do not exist.

---

## 10. Verification Before Completion

Before reporting completion:

1. Confirm this file contains the resolved stack and `READY_TO_SCAFFOLD` status.
2. Confirm `.cursor/rules/agent-orchestration.mdc` exists, uses valid YAML frontmatter, and has `alwaysApply: true`.
3. Confirm `.cursor/agents/backend.md` exists and contains YAML frontmatter with `name` and `description`.
4. Confirm `.cursor/agents/frontend.md` exists and contains YAML frontmatter with `name` and `description`.
5. Confirm `.cursor/agents/tester.md` exists and contains YAML frontmatter with `name` and `description`.
6. Confirm the orchestration rule routes backend-only, frontend-only, full-stack, testing-only, and cross-cutting tasks correctly.
7. Confirm the rule prevents unsafe same-checkout parallel writes and requires tester validation after integration.
8. Confirm `tasks/TASK_TEMPLATE.md` exists.
9. Confirm `CLAUDE.md` exists and reflects the resolved stack.
10. Confirm `.env.example` contains no real secrets.
11. Confirm Docker files reference valid project paths.
12. Validate the Compose configuration with `docker compose config` when Docker is available.
13. Confirm the documented primary local startup path uses Docker Compose.
14. Run the strongest available verification sequence for the selected stack.
15. Fix scaffold errors that are within scope before reporting completion.

Prefer one concise verification sequence instead of narrating every command.

---

## 11. Final Completion Report

After scaffolding and verification, provide a short report using this format:

```markdown
## Bootstrap Report

### Resolved Stack
- Frontend: ...
- Backend: ...
- Database: ...
- Local environment: Docker + Docker Compose

### Created / Updated
- ...

### Verification
- `<command>` — passed / failed

### Assumptions
- None.

### Notes
- Only include material implementation notes.

### Token Usage
- Input tokens: <exact value if exposed by the runtime; otherwise `not exposed`>
- Output tokens: <exact value if exposed by the runtime; otherwise `not exposed`>
- Estimated tokens used to generate project files: <estimate only if exact usage is unavailable>
- Measurement: `exact` or `estimated`
```

Token reporting rules:

- Never fabricate exact token counts.
- If the execution environment exposes token usage metadata, use it.
- If exact usage is unavailable, say `not exposed` for exact counts and provide only a clearly labeled estimate for the content generated during scaffolding.
- Keep the token report to four lines maximum.
- Do not include a long explanation of tokenization.

---

## 12. Completion Behavior

The bootstrap is complete only when:

- the two-step interactive planning flow has been completed,
- this file has been updated with the resolved configuration,
- the scaffold has been created,
- `.cursor/rules/agent-orchestration.mdc` exists and persistently defines task classification, specialist delegation, safe parallelism, integration, and tester validation,
- the required agent files have valid Cursor YAML frontmatter and have been filled with the selected stack,
- `tasks/TASK_TEMPLATE.md` exists,
- `CLAUDE.md` reflects the project-wide rules,
- Docker and Docker Compose are configured as the canonical local development environment and are consistent with the selected stack,
- verification has been attempted and results have been reported,
- the final concise token usage report has been included.

Do not continue with unrelated feature development after bootstrap completion unless the user explicitly asks for it.
