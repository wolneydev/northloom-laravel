<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## About This Project

Hospitable is a Laravel API for managing projects, calendar tasks, funds, costs, and financial allocations, with Telegram reminders and aggregated reporting. API authentication uses [Laravel Passport](https://laravel.com/docs/passport) (Bearer tokens).

## Features

### REST API & Swagger Documentation

The application exposes a JSON REST API (`routes/api.php`) covering:

- Auth (login/logout via Passport) and user sign-up
- Projects and calendar Tasks (CRUD)
- Project Funds and Costs
- Task financial allocations
- Aggregated Reports (projects/tasks)
- Telegram notification preferences

Interactive OpenAPI/Swagger documentation is generated with [`darkaonline/l5-swagger`](https://github.com/DarkaOnLine/L5-Swagger) from PHP attributes on the controllers (`app/Http/Controllers/Api/*`) and reusable schemas in `app/OpenApi/Schemas.php`.

- UI: `/api/documentation`
- Regenerate spec: `php artisan l5-swagger:generate` (output in `storage/api-docs/`)

### MCP Server (Model Context Protocol)

A native MCP server, built with the official [`laravel/mcp`](https://github.com/laravel/mcp) package, exposes the app's core domain actions as tools for AI agents/LLM clients.

- Server class: `app/Mcp/Servers/HospitableServer.php`
- Tools: `app/Mcp/Tools/`
- Registered in `routes/ai.php` (stdio transport, handle `hospitable`)

**Chat HTTP API:** authenticated users can talk to these tools via `POST /api/chat`
(`App\Ai\Agents\HospitableChatAgent` + `laravel/ai`). The agent connects to this
same local MCP handle and uses **local Ollama only** (see `config/ai.php`).
Set `OLLAMA_URL` / `AI_MODEL` in `.env`, then run migrations so conversation
tables exist.

**Authentication:** the MCP server has no login/sign-up flow of its own. Every tool call acts on behalf of a fixed service account, resolved from `USER_LOGIN` / `PASSWORD_USER` in `.env` via `App\Mcp\Concerns\AuthenticatesMcpUser`. This is independent of the API's Passport-based authentication.

**Available tools:**

| Tool | Description |
|---|---|
| `StoreProjectTool` | Create a project |
| `UpdateProjectTool` | Update a project |
| `StoreTaskTool` | Create a calendar task |
| `UpdateTaskTool` | Update a calendar task |
| `StoreFundTool` | Create a project fund |
| `StoreCostTool` | Create a project cost |
| `StoreFinancialAllocationTool` | Allocate fund money to a task |
| `UpdateTelegramSettingsTool` | Update Telegram notification preferences |
| `GetProjectReportTool` | Aggregated projects/tasks report |

**Running the server:**

```bash
php artisan mcp:start hospitable   # stdio transport
php artisan mcp:inspector          # interactive debugging
```

To connect an MCP-capable client (e.g. Claude Code) running on the host against the containerized app, register it in `.mcp.json`:

```json
{
  "mcpServers": {
    "hospitable": {
      "command": "docker",
      "args": ["compose", "exec", "-T", "app", "php", "artisan", "mcp:start", "hospitable"]
    }
  }
}
```

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
