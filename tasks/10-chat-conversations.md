# 10 Chat and conversations

**Status:** Done

## Why

Users need a conversational entry point that drives Hospitable via MCP, plus a history of those threads.

Expected result: `POST /api/chat` talks to `HospitableChatAgent` (Ollama + local MCP); `GET /api/conversations` lists the caller’s threads.

---

## What

`ChatController` stores messages on a Laravel AI conversation. Agent uses `Client::local` → `mcp:start hospitable`. `ConversationService` lists/shows only the owner (403/404 otherwise).

The system must:

* Require `message`; optional `conversation_id`
* Return `conversation_id`, assistant `message`, `tool_calls`
* Not leak another user’s conversation

### Expected Behavior

#### POST /api/chat

Input:

```text
{ "message": "Liste meus projetos" }
```

Output:

```text
HTTP 200  { "data": { "conversation_id": "...", "message": "...", "tool_calls": [] } }
```

---

## Out of Scope

* Cloud LLM providers (agent is Ollama-only)
* Idea-first prompt (11)

---

## Context

* `app/Http/Controllers/Api/ChatController.php`
* `app/Ai/Agents/HospitableChatAgent.php`
* `app/Domain/Conversations/Services/ConversationService.php`
* `app/Http/Controllers/Api/ConversationController.php`

> General project rules are in `CLAUDE.md`.

---

## Tasks

### T1: Chat + history

**What:** Agent + conversation list/show.

**Files:** `ChatController.php`, `HospitableChatAgent.php`, `ConversationController.php`

**Verify**

```bash
php artisan route:list --path=chat
php artisan route:list --path=conversations
```

---

## Edge Cases & Errors

| Scenario | Expected behavior | Covered by test |
| --- | --- | --- |
| Guest | 401 | auth middleware |
| Foreign conversation | 403 | ConversationController |
| Missing conversation | 404 | ConversationController |
| Agent/MCP failure | 502 | ChatController |

Follow `app/Http/Controllers/Api/ChatController.php`.

---

## Done when

* [x] **What** behavior implemented.
* [x] Chat/conversation routes live under `auth:api`.
* [x] Agent uses local Hospitable MCP tools only.
* [x] Diff limited to scope.

---

## Report

* **Decisions:** RemembersConversations; tools from stdio MCP, not inlined in the agent.
* **Deviations:** None.
* **Remaining:** None.
