<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Domain\Users\DTOs\TelegramSettingsData;
use App\Domain\Users\Services\UserService;
use App\Mcp\Concerns\AuthenticatesMcpUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

/**
 * Mirrors the validation rules of UpdateTelegramSettingsRequest (see
 * app/OpenApi/Schemas.php -> UpdateTelegramSettingsRequest).
 */
#[Description('Atualiza as preferências de notificação do Telegram do usuário de serviço configurado no MCP (USER_LOGIN/PASSWORD_USER).')]
#[IsIdempotent(true)]
#[IsOpenWorld(false)]
final class UpdateTelegramSettingsTool extends Tool
{
    use AuthenticatesMcpUser;

    public function __construct(private readonly UserService $users) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->resolveActingUser();

        $validated = $request->validate([
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'telegram_notifications_enabled' => ['required', 'boolean'],
        ], $this->validationMessages());

        $validated['telegram_notifications_enabled'] = $request->boolean('telegram_notifications_enabled');

        $updated = $this->users->updateTelegramSettings($user, TelegramSettingsData::fromArray($validated));

        return Response::structured([
            'telegram_chat_id' => $updated->telegram_chat_id,
            'telegram_notifications_enabled' => (bool) $updated->telegram_notifications_enabled,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'telegram_chat_id' => $schema->string()
                ->description('Chat id do Telegram do usuário. Envie null/omita para limpar.')
                ->max(255)
                ->nullable(),
            'telegram_notifications_enabled' => $schema->boolean()
                ->description('Se true, habilita o envio de lembretes de tarefas via Telegram.')
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'telegram_chat_id' => $schema->string()->nullable(),
            'telegram_notifications_enabled' => $schema->boolean(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'telegram_chat_id.max' => 'O campo "telegram_chat_id" deve ter no máximo 255 caracteres.',
            'telegram_notifications_enabled.required' => 'O campo "telegram_notifications_enabled" é obrigatório.',
            'telegram_notifications_enabled.boolean' => 'O campo "telegram_notifications_enabled" deve ser verdadeiro ou falso.',
        ];
    }
}
