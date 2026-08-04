# Hospitable API — Machine-Readable Developer Documentation

```yaml
document:
  name: Hospitable API
  purpose: REST API for projects, calendar tasks, and Telegram task reminders
  audience: developers and AI coding agents
  base_path: /api
  default_base_url: http://localhost:8888/api
  related_docs:
    - docs/ARQUITETURA.md
```

---

## META

```yaml
product:
  name: Hospitable
  type: REST_API
  capabilities:
    - user_signup_login_with_passport_bearer_token
    - crud_projects_owned_by_authenticated_user
    - crud_calendar_tasks_inside_owned_projects
    - configure_telegram_chat_and_enable_flag
    - send_telegram_reminders_at_notify_at_datetime
    - filtered_reports_for_projects_and_tasks
  security_invariants:
    - all_resources_scoped_to_authenticated_owner
    - user_id_never_accepted_from_client_payload
    - non_owner_access_returns_403
    - telegram_bot_token_never_exposed_via_api
```

```yaml
stack:
  language: PHP ^8.3
  framework: Laravel ^13.8
  auth: Laravel Passport ^13.7
  auth_header: "Authorization: Bearer {access_token}"
  database: PostgreSQL (Docker) | SQLite (optional local/tests)
  http_client: Laravel HTTP (Telegram Bot API)
  tests: PHPUnit ^12.5
  runtime_optional: Docker Compose [app, nginx, db]
  timezone_default: America/Sao_Paulo
```

---

## ARCHITECTURE

```yaml
layers:
  - name: Http
    path: app/Http
    contains: [Controllers, Requests, Resources]
  - name: Domain
    path: app/Domain
    contains: [Services, DTOs, RepositoryInterfaces, NotificationContracts]
    rule: must_not_depend_on_Http_or_concrete_Infrastructure
  - name: Infrastructure
    path: app/Infrastructure
    contains: [EloquentRepositories, TelegramClient]
  - name: Models
    path: app/Models
    contains: [User, Project, Task]
  - name: Policies
    path: app/Policies
    contains: [ProjectPolicy, TaskPolicy]
  - name: Console
    path: app/Console/Commands
    contains: [SendTaskNotifications]

dependency_flow: |
  Route -> auth:api -> FormRequest(validate+DTO) -> Controller -> Service
  -> RepositoryInterface -> EloquentRepository -> Model -> Resource(JSON)

bindings_file: app/Providers/AppServiceProvider.php

domain_modules:
  Projects: [ProjectData, ProjectService, ProjectRepositoryInterface]
  Tasks: [TaskData, TaskFilters, TaskService, TaskNotificationService, TaskRepositoryInterface]
  Users: [UserData, TelegramSettingsData, UserService, UserRepositoryInterface]
  Reports: [ReportFilters, ReportService]
  Notifications: [TelegramNotificationServiceInterface]
```

---

## ENUMS

```yaml
enums:
  task_priority:
    values: [low, medium, high]
    nullable: true
  task_status:
    values: [pending, in_progress, completed, cancelled]
    default: pending
  report_type:
    values: [projects, tasks, both]
  date_format: YYYY-MM-DD
  datetime_formats_accepted:
    - ISO_datetime
    - "HH:MM"
    - "HH:MM:SS"
  datetime_response_format: ISO8601
```

```yaml
input_aliases:
  # Applied before validation on task create/update
  priority:
    baixa: low
    media: medium
    média: medium
    alta: high
  status:
    pendente: pending
    "em progresso": in_progress
    em_progresso: in_progress
    "em andamento": in_progress
    em_andamento: in_progress
    andamento: in_progress
    concluida: completed
    concluída: completed
    concluido: completed
    concluído: completed
    finalizada: completed
    cancelada: cancelled
    cancelado: cancelled
```

---

## ENTITIES

```yaml
entity: User
source: app/Models/User.php
traits: [HasApiTokens, HasFactory, Notifiable]
fields:
  - { name: id, type: integer, primary: true }
  - { name: name, type: string, required: true, max: 255 }
  - { name: email, type: string, required: true, unique: true }
  - { name: password, type: string, required: true, cast: hashed, hidden: true }
  - { name: telegram_chat_id, type: string|null, required: false, max: 255 }
  - { name: telegram_notifications_enabled, type: boolean, default: false }
  - { name: email_verified_at, type: datetime|null }
  - { name: created_at, type: datetime }
  - { name: updated_at, type: datetime }
relations:
  - { name: projects, type: has_many, target: Project }
  - { name: tasks, type: has_many, target: Task }
api_resource_fields: [id, name, email, email_verified_at, created_at, updated_at]
```

```yaml
entity: Project
source: app/Models/Project.php
fields:
  - { name: id, type: integer, primary: true }
  - { name: user_id, type: foreign_key(User), required: true, set_by: auth_token, client_writable: false }
  - { name: name, type: string, required: true, max: 255 }
  - { name: starts_on, type: date, required: true, format: YYYY-MM-DD }
  - { name: expected_ends_on, type: date, required: true, format: YYYY-MM-DD, constraint: ">= starts_on" }
  - { name: notes, type: text|null, required: false }
  - { name: created_at, type: datetime }
  - { name: updated_at, type: datetime }
relations:
  - { name: user, type: belongs_to, target: User }
  - { name: tasks, type: has_many, target: Task, on_delete: cascade }
ownership: user_id must equal auth.user.id
api_resource_fields: [id, name, starts_on, expected_ends_on, notes]
```

```yaml
entity: Task
source: app/Models/Task.php
fields:
  - { name: id, type: integer, primary: true }
  - { name: user_id, type: foreign_key(User), required: true, set_by: auth_token, client_writable: false }
  - { name: project_id, type: foreign_key(Project), required: true, constraint: project.user_id == auth.user.id }
  - { name: title, type: string, required: true, max: 255 }
  - { name: task_date, type: date, required: true, format: YYYY-MM-DD }
  - { name: starts_at, type: datetime, required: true, accepts: [ISO_datetime, time_only] }
  - { name: ends_at, type: datetime|null, required: false, constraint: "> starts_at", accepts: [ISO_datetime, time_only] }
  - { name: notes, type: text|null, required: false }
  - { name: location, type: string|null, required: false, max: 255 }
  - { name: priority, type: enum(task_priority)|null, required: false }
  - { name: status, type: enum(task_status), required: false, default: pending }
  - { name: notify, type: boolean, default: false }
  - { name: notify_at_datetime, type: datetime|null, required_if: "notify == true", accepts: [ISO_datetime, time_only] }
  - { name: notification_sent_at, type: datetime|null, system_managed: true }
  - { name: attempts, type: integer, default: 0, max: 8, system_managed: true }
  - { name: next_attempt_at, type: datetime|null, system_managed: true }
  - { name: created_at, type: datetime }
  - { name: updated_at, type: datetime }
relations:
  - { name: user, type: belongs_to, target: User }
  - { name: project, type: belongs_to, target: Project }
normalization:
  - time_only_fields: [starts_at, ends_at, notify_at_datetime]
  - combine_with: task_date
ownership: user_id must equal auth.user.id
api_resource_fields:
  - id
  - project_id
  - project_name  # present when project relation loaded
  - title
  - task_date
  - starts_at
  - ends_at
  - notes
  - location
  - priority
  - status
  - notify
  - notify_at_datetime
  - notification_sent_at
```

```yaml
relationships:
  - User 1->* Project
  - Project 1->* Task
  - User 1->* Task
```

---

## ENV

```yaml
env:
  - { name: APP_KEY, required: true, purpose: laravel_encryption_key }
  - { name: DB_*, required: true, purpose: database_connection }
  - { name: TELEGRAM_BOT_TOKEN, required: false, purpose: telegram_bot_api_token, exposed_to_clients: false }
  - { name: APP_TIMEZONE, required: false, default: America/Sao_Paulo }
  - { name: APP_PORT, required: false, default: 8888 }
  - { name: APP_URL, required: false, default: http://localhost:8888 }
```

```yaml
setup_docker:
  steps:
    - cp .env.example .env
    - docker compose up -d --build
    - docker compose exec app php artisan key:generate
    - docker compose exec app php artisan migrate
    - docker compose exec app php artisan passport:install
  api_base_url: http://localhost:8888/api

setup_local:
  steps:
    - composer install
    - cp .env.example .env
    - php artisan key:generate
    - configure DB_*
    - php artisan migrate
    - php artisan passport:install
    - php artisan serve

scheduler_required_for_notifications:
  commands:
    - php artisan schedule:work
    - "cron: * * * * * cd /path-to-app && php artisan schedule:run"
```

---

## ENDPOINTS

### conventions

```yaml
response_envelope:
  single_resource: '{ "data": { ...resource } }'
  collection: '{ "data": [ ... ], "links": ..., "meta": ... }'  # Laravel pagination
pagination:
  query_param: per_page
  default: 15
auth:
  public: [POST /api/users, POST /api/login]
  protected: all_other_endpoints
  header: "Authorization: Bearer {access_token}"
disabled_routes:
  - GET /api/users
  - GET /api/users/{user}
  - PUT|PATCH /api/users/{user}
  - DELETE /api/users/{user}
```

### auth

```yaml
endpoint:
  id: users.signup
  method: POST
  path: /api/users
  auth: none
  request:
    content_type: application/json
    body:
      - { name: name, type: string, required: true, max: 255 }
      - { name: email, type: string, required: true, unique: users.email }
      - { name: password, type: string, required: true, confirmed: true }
      - { name: password_confirmation, type: string, required: true }
  response:
    status: 201
    body: UserResource
```

```yaml
endpoint:
  id: auth.login
  method: POST
  path: /api/login
  auth: none
  request:
    content_type: application/json
    body:
      - { name: email, type: string, required: true }
      - { name: password, type: string, required: true }
  response:
    status: 200
    body:
      token_type: Bearer
      access_token: string
      user: UserResource
  errors:
    - { status: 422, when: invalid_credentials }
```

```yaml
endpoint:
  id: auth.logout
  method: POST
  path: /api/logout
  auth: bearer
  response:
    status: 200
    body: { message: string }
  effect: revoke_current_access_token
```

### telegram_settings

```yaml
endpoint:
  id: telegram.show
  method: GET
  path: /api/me/telegram
  auth: bearer
  response:
    status: 200
    body:
      data:
        telegram_chat_id: string|null
        telegram_notifications_enabled: boolean
```

```yaml
endpoint:
  id: telegram.update
  method: PUT
  path: /api/me/telegram
  auth: bearer
  request:
    content_type: application/json
    body:
      - { name: telegram_chat_id, type: string|null, required: false, max: 255 }
      - { name: telegram_notifications_enabled, type: boolean, required: true }
  response:
    status: 200
    body:
      data:
        telegram_chat_id: string|null
        telegram_notifications_enabled: boolean
```

```yaml
endpoint:
  id: telegram.test
  method: POST
  path: /api/me/telegram/test
  auth: bearer
  request:
    body: none
  preconditions:
    - telegram_chat_id must be non-empty
  response:
    status: 200
    body: { message: string }
  errors:
    - { status: 422, when: telegram_chat_id_missing }
    - { status: 502, when: telegram_delivery_failed }
```

### projects

```yaml
endpoint:
  id: projects.index
  method: GET
  path: /api/projects
  auth: bearer
  query:
    - { name: per_page, type: integer, required: false, default: 15 }
  response:
    status: 200
    body: ProjectResource[]
    scoped_to: auth.user.id
```

```yaml
endpoint:
  id: projects.store
  method: POST
  path: /api/projects
  auth: bearer
  request:
    content_type: application/json
    body:
      - { name: name, type: string, required: true, max: 255 }
      - { name: currency, type: ISO_4217_code, required: true, supported_by: FINANCIAL_CURRENCIES }
      - { name: starts_on, type: date, required: true }
      - { name: expected_ends_on, type: date, required: true, constraint: ">= starts_on" }
      - { name: notes, type: string|null, required: false }
  response:
    status: 201
    body: ProjectResource
  side_effects:
    - sets user_id from auth.user.id
    - normalizes currency to uppercase
```

```yaml
endpoint:
  id: projects.show
  method: GET
  path: /api/projects/{id}
  auth: bearer
  authorization: ProjectPolicy.view
  response:
    status: 200
    body: ProjectResource
  errors:
    - { status: 403, when: not_owner }
    - { status: 422, when: currency_is_unsupported_or_changes_after_financial_activity }
    - { status: 404, when: not_found }
```

```yaml
endpoint:
  id: projects.update
  method: PUT|PATCH
  path: /api/projects/{id}
  auth: bearer
  authorization: ProjectPolicy.update
  request:
    content_type: application/json
    body: partial_project_fields_same_as_store
  response:
    status: 200
    body: ProjectResource
  errors:
    - { status: 403, when: not_owner }
```

```yaml
endpoint:
  id: projects.destroy
  method: DELETE
  path: /api/projects/{id}
  auth: bearer
  authorization: ProjectPolicy.delete
  response:
    status: 204
    body: empty
  side_effects:
    - cascade_delete_tasks
  errors:
    - { status: 403, when: not_owner }
```

### tasks

```yaml
endpoint:
  id: tasks.index
  method: GET
  path: /api/tasks
  auth: bearer
  query:
    - { name: start, type: date, required: false, filters: task_date >= start }
    - { name: end, type: date, required: false, filters: task_date <= end, constraint: ">= start" }
    - { name: project_id, type: integer, required: false }
    - { name: status, type: enum(task_status), required: false }
    - { name: priority, type: enum(task_priority), required: false }
    - { name: per_page, type: integer, required: false, default: 15 }
  response:
    status: 200
    body: TaskResource[]
    includes: project_name via eager_load
    scoped_to: auth.user.id
```

```yaml
endpoint:
  id: tasks.store
  method: POST
  path: /api/tasks
  auth: bearer
  request:
    content_type: application/json
    body:
      - { name: project_id, type: integer, required: true, must_belong_to: auth.user }
      - { name: title, type: string, required: true, max: 255 }
      - { name: task_date, type: date, required: true }
      - { name: starts_at, type: datetime|time_only, required: true }
      - { name: ends_at, type: datetime|time_only|null, required: false, constraint: "> starts_at" }
      - { name: notes, type: string|null, required: false }
      - { name: location, type: string|null, required: false, max: 255 }
      - { name: priority, type: enum(task_priority)|alias_pt, required: false }
      - { name: status, type: enum(task_status)|alias_pt, required: false, default: pending }
      - { name: notify, type: boolean, required: false, default: false }
      - { name: notify_at_datetime, type: datetime|time_only|null, required_if: "notify == true" }
  response:
    status: 201
    body: TaskResource
  side_effects:
    - sets user_id from auth.user.id
    - normalizes time_only to datetime using task_date
    - normalizes portuguese priority/status aliases
```

```yaml
endpoint:
  id: tasks.show
  method: GET
  path: /api/tasks/{id}
  auth: bearer
  authorization: TaskPolicy.view
  response:
    status: 200
    body: TaskResource
    includes: project_name
  errors:
    - { status: 403, when: not_owner }
```

```yaml
endpoint:
  id: tasks.update
  method: PUT|PATCH
  path: /api/tasks/{id}
  auth: bearer
  authorization: TaskPolicy.update
  request:
    content_type: application/json
    body: partial_task_fields_same_as_store
  response:
    status: 200
    body: TaskResource
  errors:
    - { status: 403, when: not_owner }
```

```yaml
endpoint:
  id: tasks.destroy
  method: DELETE
  path: /api/tasks/{id}
  auth: bearer
  authorization: TaskPolicy.delete
  response:
    status: 204
    body: empty
  errors:
    - { status: 403, when: not_owner }
```

### reports

```yaml
endpoint:
  id: reports.show
  method: GET
  path: /api/reports
  auth: bearer
  query:
    - { name: report_type, type: enum(report_type), required: true }
    - { name: status, type: enum(task_status)|null, required: false, empty_means: all }
    - { name: start_date, type: date, required: false }
    - { name: end_date, type: date, required: false, constraint: ">= start_date" }
  response:
    status: 200
    body:
      data:
        filters:
          report_type: enum(report_type)
          status: enum(task_status)|null
          start_date: date|null
          end_date: date|null
        projects:  # present if report_type in [projects, both]
          - id: integer
            name: string
            starts_on: date
            expected_ends_on: date
            notes: string|null
            tasks_count: integer
            status_counts:
              pending: integer
              in_progress: integer
              completed: integer
              cancelled: integer
        tasks: TaskResource[]  # present if report_type in [tasks, both]
  scoped_to: auth.user.id
```

---

## EXAMPLE_PAYLOADS

```json
// POST /api/users
{
  "name": "Ada Lovelace",
  "email": "ada@example.com",
  "password": "secret-password",
  "password_confirmation": "secret-password"
}
```

```json
// POST /api/login
{
  "email": "ada@example.com",
  "password": "secret-password"
}
```

```json
// POST /api/login response
{
  "token_type": "Bearer",
  "access_token": "...",
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "email_verified_at": null,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

```json
// PUT /api/me/telegram
{
  "telegram_chat_id": "123456789",
  "telegram_notifications_enabled": true
}
```

```json
// POST /api/projects
{
  "name": "Website redesign",
  "starts_on": "2026-07-01",
  "expected_ends_on": "2026-08-15",
  "notes": "Optional notes"
}
```

```json
// POST /api/tasks  (time-only schedule example)
{
  "project_id": 1,
  "title": "Client call",
  "task_date": "2026-07-25",
  "starts_at": "14:00",
  "ends_at": "15:00",
  "notes": "Discuss scope",
  "location": "Zoom",
  "priority": "high",
  "status": "pending",
  "notify": true,
  "notify_at_datetime": "13:45"
}
```

```json
// TaskResource example
{
  "data": {
    "id": 10,
    "project_id": 1,
    "project_name": "Website redesign",
    "title": "Client call",
    "task_date": "2026-07-25",
    "starts_at": "2026-07-25T14:00:00+00:00",
    "ends_at": "2026-07-25T15:00:00+00:00",
    "notes": "Discuss scope",
    "location": "Zoom",
    "priority": "high",
    "status": "pending",
    "notify": true,
    "notify_at_datetime": "2026-07-25T13:45:00+00:00",
    "notification_sent_at": null
  }
}
```

```text
GET /api/reports?report_type=both&status=pending&start_date=2026-07-01&end_date=2026-07-31
```

---

## NOTIFICATIONS

```yaml
notification_system:
  channel: Telegram Bot API sendMessage
  artisan_command: tasks:send-notifications
  schedule:
    file: routes/console.php
    frequency: everyMinute
    timezone: America/Sao_Paulo
    without_overlapping_minutes: 5
  config_key: services.telegram.bot_token
  env_key: TELEGRAM_BOT_TOKEN

delivery_preconditions_all_required:
  - notification_sent_at is null
  - notify_at_datetime <= now
  - attempts < 8
  - user.telegram_notifications_enabled == true
  - user.telegram_chat_id is non-empty
  - atomic_claim_succeeds

retry_policy:
  strategy: exponential_backoff_minutes
  sequence: [1, 2, 4, 8, ...]
  max_backoff_minutes: 60
  max_attempts: 8
  claim_lease_minutes: 5
  overdue_recovery: true  # past-due unsent rows remain eligible after downtime

flow:
  - user_sets_telegram_settings
  - user_creates_task_with_notify_true_and_notify_at_datetime
  - scheduler_runs_tasks_send_notifications
  - TaskNotificationService_selects_due_unsent_tasks
  - atomically_claims_row_via_next_attempt_at
  - TelegramNotificationService_posts_to_bot_api
  - on_success: set notification_sent_at
  - on_failure: increment attempts and set next_attempt_at

message_template: |
  🔔 Lembrete de tarefa

  Projeto: {project.name}
  Tarefa: {task.title}
  Data/Hora: {task.starts_at as d/m/Y H:i}
  Local: {task.location}

  Observações:
  {task.notes}

classes:
  - { class: App\Console\Commands\SendTaskNotifications, role: artisan_entry_point }
  - { class: App\Domain\Tasks\Services\TaskNotificationService, role: due_claim_retry_message }
  - { class: App\Infrastructure\Telegram\TelegramNotificationService, role: http_transport }
```

Manual run:

```bash
php artisan tasks:send-notifications
```

---

## CODEMAP

```yaml
codemap:
  routes_api: routes/api.php
  scheduler: routes/console.php
  controllers: app/Http/Controllers/Api/
  form_requests: app/Http/Requests/
  api_resources: app/Http/Resources/
  domain_services: app/Domain/**/Services/
  repository_interfaces: app/Domain/**/Repositories/
  eloquent_repositories: app/Infrastructure/Persistence/Eloquent/
  telegram_client: app/Infrastructure/Telegram/
  models: app/Models/
  policies: app/Policies/
  di_bindings: app/Providers/AppServiceProvider.php
  migrations: database/migrations/
  feature_tests: tests/Feature/
  architecture_deep_dive: docs/ARQUITETURA.md
```

```yaml
tests:
  run:
    - php artisan test
    - composer test
  patterns:
    - RefreshDatabase
    - Passport::actingAs(user)
  coverage_areas:
    - ownership_isolation
    - projects_crud
    - tasks_crud_and_filters
    - telegram_settings
    - reports
    - notification_delivery
```

---

## QUICKSTART

```yaml
quickstart:
  - clone_and_copy_env
  - start_docker_or_configure_local_db
  - migrate_and_passport_install
  - POST /api/users then POST /api/login
  - POST /api/projects then POST /api/tasks with notify true
  - PUT /api/me/telegram then POST /api/me/telegram/test
  - run schedule:work so reminders fire
  - run php artisan test before PR
```

---

## PROJECT_FINANCIAL_MANAGEMENT

```yaml
money:
  storage: DECIMAL(15,2)
  request_and_response_type: string
  format: "non-negative or positive decimal with exactly two fractional digits"
  currency: project.currency
  supported_codes: config.financial.currencies
  default_supported_codes: [BRL, USD, EUR]
  floating_point_arithmetic: forbidden

entities:
  Project:
    added_field: { name: currency, type: char(3), nullable: legacy_projects_only }
    rules:
      - required when creating a project
      - normalized to uppercase
      - must be included in FINANCIAL_CURRENCIES
      - legacy projects cannot create financial records until configured
      - may not change after a fund or cost exists
  Fund:
    response_fields: [id, project_id, currency, name, opening_balance, available_balance, created_at, updated_at]
    ownership: fund.project.user_id == auth.user.id
    rules:
      - opening_balance >= 0
      - available_balance initialized from opening_balance
      - available_balance never negative
  Cost:
    response_fields: [id, project_id, currency, amount, description, incurred_on, created_at, updated_at]
    ownership: cost.project.user_id == auth.user.id
    rules:
      - amount > 0
      - registering a cost does not debit a fund
  FinancialAllocation:
    response_fields: [id, task_id, fund_id, currency, amount, allocated_at, created_at, updated_at]
    ownership: task.user_id == auth.user.id and fund.project.user_id == auth.user.id
    rules:
      - amount > 0
      - fund.project_id == task.project_id
      - fund.available_balance >= amount
      - allocation insert and fund debit commit or rollback together
```

### financial endpoints

All endpoints require `Authorization: Bearer {access_token}`.

```yaml
endpoints:
  - method: GET
    path: /api/projects/{project}/funds
    response: paginated FundResource collection
    errors: [401 unauthenticated, 403 not_owner, 404 project_not_found]
  - method: POST
    path: /api/projects/{project}/funds
    body:
      name: { type: string, required: true, max: 255 }
      opening_balance: { type: decimal_string, required: true, example: "1000.00" }
    response: { status: 201, body: FundResource }
    errors: [{ status: 422, when: project_currency_not_configured }]
  - method: GET
    path: /api/projects/{project}/costs
    response: paginated CostResource collection
    errors: [401 unauthenticated, 403 not_owner, 404 project_not_found]
  - method: POST
    path: /api/projects/{project}/costs
    body:
      amount: { type: positive_decimal_string, required: true, example: "125.50" }
      description: { type: string, required: true, max: 1000 }
      incurred_on: { type: date, required: true, format: YYYY-MM-DD }
    response: { status: 201, body: CostResource }
    errors: [{ status: 422, when: project_currency_not_configured }]
  - method: POST
    path: /api/tasks/{task}/financial-allocations
    body:
      fund_id: { type: integer, required: true }
      amount: { type: positive_decimal_string, required: true, example: "250.00" }
    response: { status: 201, body: FinancialAllocationResource }
    errors:
      - { status: 403, when: task_or_fund_not_owned_by_authenticated_user }
      - { status: 404, when: task_or_fund_not_found }
      - { status: 422, when: fund_and_task_belong_to_different_projects }
      - { status: 422, when: project_currency_not_configured }
      - { status: 422, when: available_balance_is_insufficient }
```

### allocation transaction

```yaml
allocation_flow:
  unit_of_work: EloquentUnitOfWork
  steps:
    - authorize route-bound task
    - begin database transaction
    - load fund with a pessimistic write lock
    - verify fund ownership
    - verify fund and task project match
    - verify project currency is configured
    - verify sufficient available balance
    - create financial allocation
    - conditionally decrement available balance
    - commit
  on_any_failure: rollback allocation and balance changes
  concurrency_guarantee: locked and guarded debit prevents aggregate allocations from exceeding balance

tests:
  sqlite:
    - php artisan test
    - php artisan test --filter=FinancialAllocation
  postgresql:
    - tests/Integration/FinancialAllocationConcurrencyTest.php
```
