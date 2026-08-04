# Guia de Implementação: Servidor MCP no Laravel 13 (`laravel/mcp`)

## Objetivo
Implementar um Servidor MCP (Model Context Protocol) nativo no Laravel 13 utilizando o pacote oficial `laravel/mcp`, mapeando os recursos e requisições definidos nos Schemas OpenAPI/Swagger do arquivo `app/OpenApi/Schemas.php`.

> **Nota de Autenticação:** O servidor MCP **NÃO** gerencia cadastro de usuários nem endpoints de login. A autenticação do próprio servidor MCP com a API Laravel deve utilizar as credenciais fixas definidas no `.env`: `USER_LOGIN` e `PASSWORD_USER`.

---

## 1. Regras de Arquitetura e Autenticação
- **Pacote Base:** `laravel/mcp`
- **Diretório das Tools:** `app/Mcp/Tools/`
- **Autenticação de Contexto:** Antes de executar qualquer ação, o servidor MCP deve utilizar o usuário autenticado via credenciais estáticas do `.env` (`USER_LOGIN` e `PASSWORD_USER`).

---

## 2. Mapeamento dos Schemas OpenAPI para MCP Tools

Crie as ferramentas MCP com base nos schemas de requisição do `Schemas.php` (desconsiderando esquemas de usuário/login):

### A. Ferramentas de Projeto (Project)
1. **`StoreProjectTool`**
   - **Schema de Referência:** `StoreProjectRequest`
   - **Inputs:** `name` (string, max:255), `currency` (string, 3 caracteres ISO), `starts_on` (date), `expected_ends_on` (date), `notes` (string, opcional).
   - **Descrição:** Cria um novo projeto associado ao usuário padrão.

2. **`UpdateProjectTool`**
   - **Schema de Referência:** `UpdateProjectRequest`
   - **Inputs:** `id` (integer, obrigatório), `name`, `currency`, `starts_on`, `expected_ends_on`, `notes` (todos opcionais).

### B. Ferramentas de Tarefas (Task)
1. **`StoreTaskTool`**
   - **Schema de Referência:** `StoreTaskRequest`
   - **Inputs:** `project_id` (integer), `title` (string), `task_date` (date), `starts_at` (string - HH:MM ou datetime), `ends_at` (string, opcional), `notes` (string, opcional), `location` (string, opcional), `priority` (enum: low, medium, high, baixa, media, alta), `status` (enum: pending, in_progress, completed, cancelled), `notify` (boolean), `notify_at_datetime` (string, opcional).

2. **`UpdateTaskTool`**
   - **Schema de Referência:** `UpdateTaskRequest`
   - **Inputs:** `id` (integer, obrigatório) + campos atualizáveis do `UpdateTaskRequest`.

### C. Ferramentas Financeiras (Funds & Costs)
1. **`StoreFundTool`**
   - **Schema de Referência:** `StoreFundRequest`
   - **Inputs:** `project_id` (integer), `name` (string), `opening_balance` (decimal 2 casas, ex: "1000.00").

2. **`StoreCostTool`**
   - **Schema de Referência:** `StoreCostRequest`
   - **Inputs:** `project_id` (integer), `amount` (decimal 2 casas), `description` (string), `incurred_on` (date).

3. **`StoreFinancialAllocationTool`**
   - **Schema de Referência:** `StoreFinancialAllocationRequest`
   - **Inputs:** `task_id` (integer), `fund_id` (integer), `amount` (decimal 2 casas).

### D. Configurações e Relatórios
1. **`UpdateTelegramSettingsTool`**
   - **Schema de Referência:** `UpdateTelegramSettingsRequest`
   - **Inputs:** `telegram_notifications_enabled` (boolean), `telegram_chat_id` (string, opcional).

2. **`GetProjectReportTool`**
   - **Schema de Referência:** `ReportFilters`
   - **Inputs:** `report_type` (projects, tasks, both), `status` (opcional), `start_date` (opcional), `end_date` (opcional).

---

## 3. Instruções de Código para a LLM

1. **Garantir contexto do usuário:** Resolva a autenticação globalmente no boot/contexto do MCP buscando o usuário correspondente às variáveis `env('USER_LOGIN')` e `env('PASSWORD_USER')`.
2. **Validações:** Aplique as regras de validação nativas descritas nos Schemas em cada classe de Tool.
3. **Erros:** Retorne mensagens claras de erro amigáveis para a LLM caso falhe alguma validação.

---

## 4. Próxima Ação
Execute a criação das classes em `app/Mcp/Tools/` omitindo qualquer recurso de login/cadastro de usuário.