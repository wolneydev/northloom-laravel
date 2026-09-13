# [TASK TITLE]

## Why

[Current problem and why it needs to be solved now. 2–4 lines, no subheadings.]

Expected result: [concrete benefit for the user or system.]

---

## What

[Desired behavior upon completion.]

The system must:

* [behavior 1]
* [behavior 2]

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

* [item 1]
* [item 2]

---

## Context

Relevant files (inspect before implementing):

* `[path]` — [responsibility] · use as reference for [pattern]
* `[path]` — [responsibility]

Task-specific constraints:

* [e.g., maintain compatibility with schema X]
* [e.g., reuse service Y instead of creating a new one]

> General project rules (stack, patterns, dependency policy,
> error handling) are in `CLAUDE.md`. Do not repeat them here.

---

## Tasks

### T1: [name]

**What:** [what to implement.]

**Files:** `[file]`, `[file]`

**Implementation**

* [technical requirement]
* [relevant edge case]

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

* [ ] **What** behavior implemented, including the cases in the table above. * [ ] New tests added; full suite passing.
* [ ] `[command: test + lint + typecheck + build]` runs without errors.
* [ ] Manual flow: `[command/request]` → `[expected result]`.
* [ ] Diff limited to scope; nothing **Out of Scope** was added.

---

## Report

Upon completion, please provide:

* Non-obvious **technical decisions** you made.
* **Deviations** from the spec (or `None.`)
* **Remaining issues** / pending items (or `None.`)