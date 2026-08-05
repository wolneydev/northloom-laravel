<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Ai\Models\ConversationMessage;

/**
 * @mixin ConversationMessage
 */
class ConversationMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'tool_calls' => $this->tool_calls ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
