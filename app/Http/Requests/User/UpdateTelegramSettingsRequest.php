<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\Users\DTOs\TelegramSettingsData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTelegramSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'telegram_notifications_enabled' => ['required', 'boolean'],
        ];
    }

    public function toData(): TelegramSettingsData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $validated['telegram_notifications_enabled'] = $this->boolean('telegram_notifications_enabled');

        return TelegramSettingsData::fromArray($validated);
    }
}
