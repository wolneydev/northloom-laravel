<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Mcp\Client;
use Stringable;

/**
 * Conversational agent that drives Hospitable domain actions through the
 * local MCP server registered as `Mcp::local('hospitable', ...)`.
 *
 * Uses local Ollama only — no external cloud LLM providers.
 */
#[Provider(Lab::Ollama)]
#[Model('qwen2.5:latest')]
class HospitableChatAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'MARKDOWN'
        You are the Hospitable planning assistant inside the Northloom app.

        You help users manage projects, calendar tasks, funds, costs, financial
        allocations, Telegram notification settings, and reports.

        Use the available MCP tools to perform actions. Prefer calling tools
        over inventing data. When required fields are missing, ask a short
        clarifying question before calling a tool.

        After a successful tool call, confirm what was done in clear, concise
        language and include important identifiers (ids, dates, amounts).

        Dates must use YYYY-MM-DD. Currency codes are 3 letters (e.g. BRL).
        Reply in the same language the user writes in.
        MARKDOWN;
    }

    /**
     * Tools exposed by the local `hospitable` MCP server (stdio).
     *
     * @return iterable<int, mixed>
     */
    public function tools(): iterable
    {
        return Client::local('php', [
            base_path('artisan'),
            'mcp:start',
            'hospitable',
        ])
            ->withTimeout(120)
            ->connect()
            ->tools();
    }
}
