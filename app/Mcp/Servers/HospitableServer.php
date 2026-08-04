<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetProjectReportTool;
use App\Mcp\Tools\StoreCostTool;
use App\Mcp\Tools\StoreFinancialAllocationTool;
use App\Mcp\Tools\StoreFundTool;
use App\Mcp\Tools\StoreProjectTool;
use App\Mcp\Tools\StoreTaskTool;
use App\Mcp\Tools\UpdateProjectTool;
use App\Mcp\Tools\UpdateTaskTool;
use App\Mcp\Tools\UpdateTelegramSettingsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Hospitable')]
#[Version('0.2.0')]
#[Instructions(<<<'MARKDOWN'
    Ferramentas para gerenciar projetos, tarefas, fundos, custos, alocações
    financeiras, preferências de Telegram e relatórios da aplicação Hospitable.

    Todas as ações são executadas em nome do usuário de serviço fixo
    configurado em USER_LOGIN/PASSWORD_USER (.env) — este servidor não
    possui cadastro nem login de usuários.
    MARKDOWN
)]
class HospitableServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        StoreProjectTool::class,
        UpdateProjectTool::class,
        StoreTaskTool::class,
        UpdateTaskTool::class,
        StoreFundTool::class,
        StoreCostTool::class,
        StoreFinancialAllocationTool::class,
        UpdateTelegramSettingsTool::class,
        GetProjectReportTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
