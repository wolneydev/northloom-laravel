<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Central place for reusable OpenAPI component schemas.
 *
 * This class holds no code; it exists only so swagger-php has somewhere to
 * anchor the #[OA\Schema] attributes referenced by the controllers' path
 * annotations via `ref: '#/components/schemas/...'`.
 */
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Ana Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@example.com'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'StoreUserRequest',
    type: 'object',
    required: ['name', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Ana Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'S3nhaForte!123'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'S3nhaForte!123'),
    ],
)]
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'S3nhaForte!123'),
    ],
)]
#[OA\Schema(
    schema: 'LoginResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
)]
#[OA\Schema(
    schema: 'Project',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Reforma da Cozinha'),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'starts_on', type: 'string', format: 'date', example: '2026-08-01'),
        new OA\Property(property: 'expected_ends_on', type: 'string', format: 'date', example: '2026-12-01'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Projeto piloto'),
    ],
)]
#[OA\Schema(
    schema: 'StoreProjectRequest',
    type: 'object',
    required: ['name', 'currency', 'starts_on', 'expected_ends_on'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Reforma da Cozinha'),
        new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3, example: 'BRL', description: 'Código ISO da moeda; deve estar entre as moedas configuradas em financial.currencies.'),
        new OA\Property(property: 'starts_on', type: 'string', format: 'date', example: '2026-08-01'),
        new OA\Property(property: 'expected_ends_on', type: 'string', format: 'date', example: '2026-12-01', description: 'Deve ser igual ou posterior a starts_on.'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Projeto piloto'),
    ],
)]
#[OA\Schema(
    schema: 'UpdateProjectRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Reforma da Cozinha'),
        new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3, example: 'BRL'),
        new OA\Property(property: 'starts_on', type: 'string', format: 'date', example: '2026-08-01'),
        new OA\Property(property: 'expected_ends_on', type: 'string', format: 'date', example: '2026-12-01'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Projeto piloto'),
    ],
    description: 'Todos os campos são opcionais (envie apenas o que deseja alterar).',
)]
#[OA\Schema(
    schema: 'ProjectReport',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Reforma da Cozinha'),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'starts_on', type: 'string', format: 'date'),
        new OA\Property(property: 'expected_ends_on', type: 'string', format: 'date'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'tasks_count', type: 'integer', example: 12),
        new OA\Property(
            property: 'status_counts',
            type: 'object',
            properties: [
                new OA\Property(property: 'pending', type: 'integer', example: 3),
                new OA\Property(property: 'in_progress', type: 'integer', example: 2),
                new OA\Property(property: 'completed', type: 'integer', example: 6),
                new OA\Property(property: 'cancelled', type: 'integer', example: 1),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'Task',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'project_id', type: 'integer', example: 1),
        new OA\Property(property: 'project_name', type: 'string', nullable: true, example: 'Reforma da Cozinha'),
        new OA\Property(property: 'title', type: 'string', example: 'Comprar materiais'),
        new OA\Property(property: 'task_date', type: 'string', format: 'date', example: '2026-08-05'),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', example: '2026-08-05T09:00:00-03:00'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true, example: '2026-08-05T10:00:00-03:00'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'location', type: 'string', nullable: true, example: 'Loja de materiais'),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], nullable: true, example: 'medium'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'], example: 'pending'),
        new OA\Property(property: 'notify', type: 'boolean', example: true),
        new OA\Property(property: 'notify_at_datetime', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'notification_sent_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'StoreTaskRequest',
    type: 'object',
    required: ['project_id', 'title', 'task_date', 'starts_at'],
    properties: [
        new OA\Property(property: 'project_id', type: 'integer', example: 1, description: 'Deve pertencer ao usuário autenticado.'),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Comprar materiais'),
        new OA\Property(property: 'task_date', type: 'string', format: 'date', example: '2026-08-05'),
        new OA\Property(property: 'starts_at', type: 'string', example: '09:00', description: 'Aceita hora (HH:MM) — combinada com task_date — ou datetime completo.'),
        new OA\Property(property: 'ends_at', type: 'string', nullable: true, example: '10:00'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'location', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'baixa', 'media', 'alta'], nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled', 'pendente', 'em_andamento', 'concluida', 'cancelada'], nullable: true),
        new OA\Property(property: 'notify', type: 'boolean', example: true),
        new OA\Property(property: 'notify_at_datetime', type: 'string', nullable: true, description: 'Obrigatório quando notify=true.'),
    ],
)]
#[OA\Schema(
    schema: 'UpdateTaskRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'project_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 255),
        new OA\Property(property: 'task_date', type: 'string', format: 'date'),
        new OA\Property(property: 'starts_at', type: 'string', example: '09:00'),
        new OA\Property(property: 'ends_at', type: 'string', nullable: true, example: '10:00'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'location', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled']),
        new OA\Property(property: 'notify', type: 'boolean'),
        new OA\Property(property: 'notify_at_datetime', type: 'string', nullable: true),
    ],
    description: 'Todos os campos são opcionais (envie apenas o que deseja alterar).',
)]
#[OA\Schema(
    schema: 'Fund',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'project_id', type: 'integer', example: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'name', type: 'string', example: 'Verba principal'),
        new OA\Property(property: 'opening_balance', type: 'string', example: '1000.00'),
        new OA\Property(property: 'available_balance', type: 'string', example: '850.00'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'StoreFundRequest',
    type: 'object',
    required: ['name', 'opening_balance'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Verba principal'),
        new OA\Property(property: 'opening_balance', type: 'string', example: '1000.00', description: 'Formato decimal com 2 casas, ex: 1000.00.'),
    ],
)]
#[OA\Schema(
    schema: 'Cost',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'project_id', type: 'integer', example: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'amount', type: 'string', example: '150.00'),
        new OA\Property(property: 'description', type: 'string', example: 'Compra de cimento'),
        new OA\Property(property: 'incurred_on', type: 'string', format: 'date', example: '2026-08-02'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'StoreCostRequest',
    type: 'object',
    required: ['amount', 'description', 'incurred_on'],
    properties: [
        new OA\Property(property: 'amount', type: 'string', example: '150.00', description: 'Formato decimal com 2 casas, ex: 150.00.'),
        new OA\Property(property: 'description', type: 'string', maxLength: 1000, example: 'Compra de cimento'),
        new OA\Property(property: 'incurred_on', type: 'string', format: 'date', example: '2026-08-02'),
    ],
)]
#[OA\Schema(
    schema: 'FinancialAllocation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'task_id', type: 'integer', example: 1),
        new OA\Property(property: 'fund_id', type: 'integer', example: 1),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'amount', type: 'string', example: '200.00'),
        new OA\Property(property: 'allocated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'StoreFinancialAllocationRequest',
    type: 'object',
    required: ['fund_id', 'amount'],
    properties: [
        new OA\Property(property: 'fund_id', type: 'integer', example: 1),
        new OA\Property(property: 'amount', type: 'string', example: '200.00', description: 'Formato decimal com 2 casas, ex: 200.00.'),
    ],
)]
#[OA\Schema(
    schema: 'TelegramSettings',
    type: 'object',
    properties: [
        new OA\Property(property: 'telegram_chat_id', type: 'string', nullable: true, example: '123456789'),
        new OA\Property(property: 'telegram_notifications_enabled', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'UpdateTelegramSettingsRequest',
    type: 'object',
    required: ['telegram_notifications_enabled'],
    properties: [
        new OA\Property(property: 'telegram_chat_id', type: 'string', nullable: true, maxLength: 255, example: '123456789'),
        new OA\Property(property: 'telegram_notifications_enabled', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'Conversation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9c6b3e2a-2b7a-4e2a-8f2a-2b7a4e2a8f2a'),
        new OA\Property(property: 'title', type: 'string', example: 'Criação do projeto ERP'),
        new OA\Property(property: 'agent', type: 'string', nullable: true, description: 'Classe do agente que respondeu à última mensagem da conversa.', example: 'App\\Ai\\Agents\\HospitableChatAgent'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'ConversationMessage',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'role', type: 'string', example: 'user'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(
            property: 'tool_calls',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'arguments', type: 'object'),
                ],
                type: 'object',
            ),
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'ConversationDetail',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Conversation'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'messages',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ConversationMessage'),
                ),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'ReportFilters',
    type: 'object',
    properties: [
        new OA\Property(property: 'report_type', type: 'string', enum: ['projects', 'tasks', 'both'], example: 'both'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed', 'cancelled'], nullable: true),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'ReportResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'filters', ref: '#/components/schemas/ReportFilters'),
                new OA\Property(property: 'projects', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectReport')),
                new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(ref: '#/components/schemas/Task')),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Not found.'),
    ],
)]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully.'),
    ],
)]
final class Schemas
{
    //
}
