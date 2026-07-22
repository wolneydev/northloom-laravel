<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Exposes only the user's Telegram preferences.
 *
 * The bot token is server-side configuration and is never serialized here.
 *
 * @mixin User
 */
class TelegramSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'telegram_chat_id' => $this->telegram_chat_id,
            'telegram_notifications_enabled' => (bool) $this->telegram_notifications_enabled,
        ];
    }
}
