# Arquitetura do Projeto — Hospitable API

> Documento de apresentação e passagem de conhecimento (knowledge transfer).
> Explica **todas as decisões arquiteturais**, o **fluxo completo de uma requisição** e
> aponta **em qual arquivo** cada decisão acontece, com trechos de código.

---

## 1. Visão geral

A aplicação é uma **API REST** para planejamento de **projetos** e **tarefas de calendário**,
construída em **Laravel 13 / PHP 8.3**, com autenticação via **Laravel Passport (OAuth2 / Bearer token)**.

O ponto central da arquitetura é separar o framework (Laravel/Eloquent) das **regras de
negócio**. Para isso adotamos uma variação pragmática de **Clean Architecture + DDD leve**,
organizada em camadas com dependências apontando sempre para **abstrações** (interfaces),
nunca para implementações concretas.

### Stack principal

| Camada | Tecnologia | Onde |
| --- | --- | --- |
| Framework | Laravel `^13.8` | `composer.json` |
| Linguagem | PHP `^8.3` (`readonly`, `enums`, atributos) | `composer.json` |
| Autenticação | Laravel Passport `^13.7` (OAuth2) | `config/auth.php` |
| Banco | SQLite (dev) via Eloquent | `database/database.sqlite` |
| Testes | PHPUnit `^12.5` | `tests/` |

---

## 2. Decisões arquiteturais (resumo)

| # | Decisão | Por quê | Arquivo-chave |
| --- | --- | --- | --- |
| 1 | **Camadas Domain / Infrastructure / Http** | Isolar regras de negócio do framework | `app/Domain/*`, `app/Infrastructure/*`, `app/Http/*` |
| 2 | **Repository Pattern (interface + Eloquent)** | Trocar persistência sem afetar negócio | `app/Domain/**/Repositories`, `app/Infrastructure/Persistence/Eloquent` |
| 3 | **Inversão de dependência via container** | Depender de abstrações | `app/Providers/AppServiceProvider.php` |
| 4 | **DTOs imutáveis** | Contrato estável entre HTTP e domínio | `app/Domain/**/DTOs` |
| 5 | **Service Layer** | Regras de negócio testáveis isoladamente | `app/Domain/**/Services` |
| 6 | **Controllers "magros" (thin)** | Controller só orquestra | `app/Http/Controllers/Api` |
| 7 | **Form Requests para validação** | Validação fora do controller | `app/Http/Requests` |
| 8 | **API Resources para serialização** | Controle do JSON de saída | `app/Http/Resources` |
| 9 | **Policies para autorização** | Dono só acessa o que é dele | `app/Policies` |
| 10 | **Autenticação stateless (Passport)** | API com Bearer token | `routes/api.php`, `config/auth.php` |
| 11 | **Normalização de input (trait)** | Aceitar formatos PT-BR e hora-only | `app/Http/Requests/Task/Concerns` |
| 12 | **Respostas JSON de exceção para `api/*`** | Erros consistentes na API | `bootstrap/app.php` |

---

## 3. Estrutura de pastas

```
app/
├── Domain/                      # Núcleo de negócio (sem Laravel)
│   ├── Projects/
│   │   ├── DTOs/                # ProjectData
│   │   ├── Repositories/        # ProjectRepositoryInterface (contrato)
│   │   └── Services/            # ProjectService (regras de negócio)
│   ├── Tasks/
│   │   ├── DTOs/                # TaskData, TaskFilters
│   │   ├── Repositories/        # TaskRepositoryInterface
│   │   └── Services/            # TaskService
│   └── Users/                   # mesma estrutura
│
├── Infrastructure/              # Detalhes técnicos (Eloquent)
│   └── Persistence/Eloquent/    # Implementações concretas dos repositórios
│
├── Http/                        # Camada de entrega (web/api)
│   ├── Controllers/Api/         # Controllers magros
│   ├── Requests/                # Validação (Form Requests)
│   └── Resources/               # Serialização de saída
│
├── Models/                      # Eloquent (Project, Task, User)
├── Policies/                    # Autorização
└── Providers/                   # Bindings do container
```

A regra de ouro: **`Domain` não conhece `Http` nem `Infrastructure`**. As setas de
dependência apontam de fora (Http) para dentro (Domain), e a `Infrastructure` implementa
contratos definidos no `Domain`.

```
HTTP ─► Controller ─► Service ─► RepositoryInterface ◄─ EloquentRepository ─► Banco
                          (Domain)        (Domain)            (Infrastructure)
```

---

## 4. As camadas em detalhe

### 4.1 Rotas — ponto de entrada

**Arquivo:** `routes/api.php`

Decisão: rotas públicas mínimas (cadastro e login) e todo o resto protegido pelo guard
`auth:api` do Passport. Os recursos usam `apiResource` (CRUD REST padronizado).

```9:25:routes/api.php
// Public routes: sign-up and login must stay reachable without a token.
Route::post('users', [UserController::class, 'store']);
Route::post('login', [AuthController::class, 'login']);

// Protected routes: everything else requires a valid Passport access token (JWT).
Route::middleware('auth:api')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    // ...
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('tasks', TaskController::class);
});
```

O guard `api` é configurado com driver `passport`:

```46:49:config/auth.php
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
```

---

### 4.2 Controller — fronteira HTTP magra

**Arquivo:** `app/Http/Controllers/Api/ProjectController.php`

Decisão: o controller **não contém regra de negócio**. Ele apenas:
1. recebe o Form Request (já validado),
2. delega ao Service,
3. autoriza via Policy,
4. devolve um Resource.

O Service é injetado pelo **constructor injection** (resolvido pelo container).

```25:47:app/Http/Controllers/Api/ProjectController.php
final class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projects,
    ) {}

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->toData());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
```

Repare em `show/update/destroy`: a autorização é explícita com `Gate::authorize`, garantindo
que apenas o dono acesse o recurso.

```49:54:app/Http/Controllers/Api/ProjectController.php
    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return ProjectResource::make($project);
    }
```

---

### 4.3 Form Request — validação e montagem do DTO

**Arquivo:** `app/Http/Requests/Project/StoreProjectRequest.php`

Decisão: validação **fora do controller**. O Form Request também é responsável por
converter o input validado em um **DTO**, injetando dados de contexto (como o `user_id` do
usuário autenticado) — assim o cliente nunca define o dono do recurso.

```20:37:app/Http/Requests/Project/StoreProjectRequest.php
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'expected_ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): ProjectData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();
        $validated['user_id'] = $this->user()->id;   // dono vem do token, não do cliente

        return ProjectData::fromArray($validated);
    }
```

**Detalhe esperto (update parcial):** em `UpdateProjectRequest`, quando só uma das datas é
enviada, o `prepareForValidation` preenche a outra a partir do valor já persistido, para que
a regra `after_or_equal` sempre tenha os dois lados disponíveis.

```35:53:app/Http/Requests/Project/UpdateProjectRequest.php
    protected function prepareForValidation(): void
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return;
        }

        $hasStart = $this->has('starts_on');
        $hasEnd = $this->has('expected_ends_on');

        if ($hasStart && ! $hasEnd) {
            $this->merge(['expected_ends_on' => $project->expected_ends_on?->toDateString()]);
        }

        if ($hasEnd && ! $hasStart) {
            $this->merge(['starts_on' => $project->starts_on?->toDateString()]);
        }
    }
```

---

### 4.4 DTO — contrato imutável entre camadas

**Arquivo:** `app/Domain/Projects/DTOs/ProjectData.php`

Decisão: usar **DTOs `readonly`** (imutáveis) e **framework-agnostic** para transportar
dados entre o HTTP e o domínio. Isso evita espalhar `$request->input(...)` ou arrays soltos
pela aplicação. O ponto mais importante é o `toArray()` com `array_filter`: **só os campos
informados** são enviados ao banco — o mesmo DTO serve para *create* (tudo) e *update*
parcial (sem sobrescrever colunas não enviadas com `null`).

```43:55:app/Domain/Projects/DTOs/ProjectData.php
    public function toArray(): array
    {
        return array_filter(
            [
                'user_id' => $this->user_id,
                'name' => $this->name,
                'starts_on' => $this->starts_on,
                'expected_ends_on' => $this->expected_ends_on,
                'notes' => $this->notes,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
```

Para tarefas, há ainda um DTO específico de **filtros de busca** (`TaskFilters`), separando o
que é "dado da entidade" do que é "critério de consulta".

```10:18:app/Domain/Tasks/DTOs/TaskFilters.php
final readonly class TaskFilters
{
    public function __construct(
        public ?string $start = null,
        public ?string $end = null,
        public ?int $project_id = null,
        public ?string $status = null,
        public ?string $priority = null,
    ) {}
```

---

### 4.5 Service — regras de negócio

**Arquivo:** `app/Domain/Projects/Services/ProjectService.php`

Decisão: o Service concentra a **lógica de negócio** e depende apenas da **interface** do
repositório (não do Eloquent). É `final readonly` e recebe a dependência por construtor.

```18:35:app/Domain/Projects/Services/ProjectService.php
final readonly class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {}

    public function listForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($userId, $perPage);
    }

    public function create(ProjectData $data): Project
    {
        return $this->projects->create($data);
    }
```

O `UserService` mostra regra de negócio um pouco mais rica: lançar `ModelNotFoundException`
quando o registro não existe, em vez de retornar `null`.

```36:45:app/Domain/Users/Services/UserService.php
    public function find(int $id): User
    {
        return $this->users->findById($id)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$id]);
    }
```

---

### 4.6 Repository — contrato + implementação

Decisão central da arquitetura: o **Repository Pattern**. O domínio define **o que** precisa
(interface); a infraestrutura define **como** (Eloquent).

**Contrato (Domain):** `app/Domain/Projects/Repositories/ProjectRepositoryInterface.php`

```17:28:app/Domain/Projects/Repositories/ProjectRepositoryInterface.php
interface ProjectRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(ProjectData $data): Project;

    public function update(Project $project, ProjectData $data): Project;

    public function delete(Project $project): bool;
}
```

**Implementação (Infrastructure):** `app/Infrastructure/Persistence/Eloquent/EloquentProjectRepository.php`
— **único lugar** que conhece Eloquent para projetos.

```18:42:app/Infrastructure/Persistence/Eloquent/EloquentProjectRepository.php
final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(ProjectData $data): Project
    {
        return Project::query()->create($data->toArray());
    }

    public function update(Project $project, ProjectData $data): Project
    {
        $project->fill($data->toArray());
        $project->save();

        return $project->refresh();
    }
```

No repositório de tarefas vê-se uma consulta com **filtros condicionais** (`when`) e
**eager loading** (`with('project')`) para evitar N+1:

```24:37:app/Infrastructure/Persistence/Eloquent/EloquentTaskRepository.php
    public function paginateForUser(int $userId, TaskFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Task::query()
            ->with('project')
            ->where('user_id', $userId)
            ->when($filters->start !== null, fn ($query) => $query->whereDate('task_date', '>=', $filters->start))
            ->when($filters->end !== null, fn ($query) => $query->whereDate('task_date', '<=', $filters->end))
            ->when($filters->project_id !== null, fn ($query) => $query->where('project_id', $filters->project_id))
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->when($filters->priority !== null, fn ($query) => $query->where('priority', $filters->priority))
            ->orderBy('task_date')
            ->orderBy('starts_at')
            ->paginate($perPage);
    }
```

---

### 4.7 Inversão de dependência — o "cola" do container

**Arquivo:** `app/Providers/AppServiceProvider.php`

Decisão: amarrar cada **interface** à sua **implementação** Eloquent. É isso que permite ao
Service pedir `ProjectRepositoryInterface` no construtor e o Laravel injetar
`EloquentProjectRepository` automaticamente. Trocar o banco no futuro = trocar uma linha aqui.

```26:30:app/Providers/AppServiceProvider.php
    public array $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
        ProjectRepositoryInterface::class => EloquentProjectRepository::class,
        TaskRepositoryInterface::class => EloquentTaskRepository::class,
    ];
```

O mesmo provider registra as **Policies**:

```43:47:app/Providers/AppServiceProvider.php
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
```

---

### 4.8 Policy — autorização (ownership)

**Arquivo:** `app/Policies/ProjectPolicy.php`

Decisão: cada recurso só pode ser acessado pelo seu **dono**. A verificação é centralizada e
reutilizada por `view/update/delete`.

```13:33:app/Policies/ProjectPolicy.php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }
    // update / delete idênticos...

    private function owns(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
```

---

### 4.9 Resource — serialização da resposta

**Arquivo:** `app/Http/Resources/ProjectResource.php`

Decisão: a forma do JSON de saída é controlada explicitamente (não expõe o model cru).
Datas são formatadas de modo consistente.

```19:28:app/Http/Resources/ProjectResource.php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'starts_on' => $this->starts_on?->toDateString(),
            'expected_ends_on' => $this->expected_ends_on?->toDateString(),
            'notes' => $this->notes,
        ];
    }
```

O `TaskResource` usa `whenLoaded` para só incluir o nome do projeto **se** a relação foi
carregada — coerente com o eager loading do repositório e evitando queries extras.

```19:24:app/Http/Resources/TaskResource.php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->whenLoaded('project', fn () => $this->project->name),
```

---

### 4.10 Model — Eloquent + relações

**Arquivo:** `app/Models/Project.php`

Decisão: models enxutos, usando **atributos PHP 8** (`#[Fillable]`), `casts()` para tipar
datas e relações declaradas com tipagem genérica.

```19:50:app/Models/Project.php
#[Fillable(['user_id', 'name', 'starts_on', 'expected_ends_on', 'notes'])]
class Project extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'expected_ends_on' => 'date',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
```

O `User` adiciona `HasApiTokens` (Passport) e esconde campos sensíveis com `#[Hidden]`:

```15:20:app/Models/User.php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
```

---

### 4.11 Migrations — modelagem e índices

**Arquivo:** `database/migrations/2026_06_16_193040_create_tasks_table.php`

Decisão: integridade por **chaves estrangeiras com `cascadeOnDelete`** e **índice composto**
pensado para as consultas de calendário (filtra por dono + intervalo de datas).

```14:32:database/migrations/2026_06_16_193040_create_tasks_table.php
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('task_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            // ...
            $table->string('status')->default('pending');
            $table->boolean('notify')->default(false);
            $table->unsignedInteger('notify_minutes_before')->nullable();
            $table->timestamps();

            // Calendar queries filter by owner and day range, so index both.
            $table->index(['user_id', 'task_date']);
        });
```

---

### 4.12 Normalização de input — trait reutilizável

**Arquivo:** `app/Http/Requests/Task/Concerns/NormalizesTaskInput.php`

Decisão: aceitar entrada amigável (hora-only e rótulos em português) e convertê-la para o
formato canônico **antes** da validação, mantendo a lógica reutilizável entre `Store` e
`Update` via **trait**.

```49:64:app/Http/Requests/Task/Concerns/NormalizesTaskInput.php
    private function combineDateAndTime(?string $date, ?string $value): ?string
    {
        if ($value === null || $date === null) {
            return $value;
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value) !== 1) {
            return $value;
        }

        if (substr_count($value, ':') === 1) {
            $value .= ':00';
        }

        return $date.' '.$value;
    }
```

Usada em `StoreTaskRequest::prepareForValidation()`, junto com uma regra importante de
segurança: a tarefa só pode ser ligada a um **projeto do próprio usuário**.

```27:36:app/Http/Requests/Task/StoreTaskRequest.php
            'project_id' => [
                'required',
                'integer',
                // A task may only be attached to a project owned by the caller.
                Rule::exists('projects', 'id')->where('user_id', $this->user()->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'task_date' => ['required', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
```

---

### 4.13 Tratamento de erros da API

**Arquivo:** `bootstrap/app.php`

Decisão: qualquer exceção em rotas `api/*` é renderizada como **JSON** (e não HTML),
garantindo respostas consistentes para consumidores da API.

```18:21:bootstrap/app.php
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
```

---

## 5. Fluxo completo de uma requisição

Exemplo: **`POST /api/tasks`** (criar uma tarefa). Acompanhe a passagem por cada camada e
o arquivo responsável.

```
Cliente
  │  POST /api/tasks  (Authorization: Bearer <token>)
  ▼
[1] routes/api.php
     • casa a rota dentro do grupo middleware('auth:api')
  ▼
[2] Middleware auth:api  (config/auth.php → driver passport)
     • valida o Bearer token e resolve o usuário autenticado
  ▼
[3] StoreTaskRequest  (app/Http/Requests/Task/StoreTaskRequest.php)
     • prepareForValidation(): trait normaliza hora-only e rótulos PT-BR
     • rules(): valida campos + checa que o project_id é do usuário
     • toData(): injeta user_id do token e devolve um TaskData (DTO)
  ▼
[4] TaskController::store  (app/Http/Controllers/Api/TaskController.php)
     • apenas delega: $this->tasks->create($request->toData())
  ▼
[5] TaskService::create  (app/Domain/Tasks/Services/TaskService.php)
     • regra de negócio; depende de TaskRepositoryInterface
  ▼
[6] AppServiceProvider  (app/Providers/AppServiceProvider.php)
     • o container resolve a interface → EloquentTaskRepository
  ▼
[7] EloquentTaskRepository::create  (app/Infrastructure/.../EloquentTaskRepository.php)
     • Task::create($data->toArray())  → INSERT
     • ->load('project')  (eager loading)
  ▼
[8] Model Task + Migration tasks  (app/Models/Task.php, database/migrations/...)
     • persiste no banco com casts e FKs
  ▼
[9] TaskResource  (app/Http/Resources/TaskResource.php)
     • serializa o JSON de saída (project_name via whenLoaded)
  ▼
Resposta  201 Created  +  { "data": { ...task... } }
```

Trecho do controller que costura tudo (note como ele é **fino**):

```40:47:app/Http/Controllers/Api/TaskController.php
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($request->toData());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
```

### Fluxo de leitura com autorização (`GET /api/tasks/{task}`)

Aqui entra a **Policy**. O `Route Model Binding` resolve a `Task` pelo id, e o controller
chama `Gate::authorize('view', $task)` — se o usuário não for o dono, retorna **403**.

```49:54:app/Http/Controllers/Api/TaskController.php
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return TaskResource::make($task->load('project'));
    }
```

### Fluxo de autenticação (`POST /api/login`)

**Arquivo:** `app/Http/Controllers/Api/AuthController.php` — valida credenciais e emite o
token Passport.

```26:46:app/Http/Controllers/Api/AuthController.php
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('api')->accessToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => UserResource::make($user),
        ]);
    }
```

---

## 6. Estratégia de testes

**Arquivos:** `tests/Feature/ProjectTest.php`, `tests/Feature/TaskTest.php`

Decisão: testes de **feature** ponta a ponta sobre a API, usando `RefreshDatabase` e
`Passport::actingAs` para simular usuário autenticado. Cobrem o caminho feliz, validação e
**isolamento entre usuários** (ownership).

```91:100:tests/Feature/ProjectTest.php
    public function test_user_cannot_access_another_users_project(): void
    {
        $project = Project::factory()->create();

        Passport::actingAs(User::factory()->create());

        $this->getJson("/api/projects/{$project->id}")->assertForbidden();
        $this->putJson("/api/projects/{$project->id}", ['name' => 'x'])->assertForbidden();
        $this->deleteJson("/api/projects/{$project->id}")->assertForbidden();
    }
```

Rodar a suíte:

```bash
php artisan test
```

---

## 7. Pontos para destacar na apresentação

- **Testabilidade:** services dependem de interfaces → fácil mockar repositórios em testes
  unitários, sem tocar no banco.
- **Baixo acoplamento ao framework:** o `Domain` não importa nada de Eloquent/HTTP; trocar
  Eloquent por outra fonte de dados é mudar bindings + criar uma nova implementação.
- **Segurança por padrão:** o dono do recurso vem sempre do token (nunca do payload), e o
  acesso é checado por Policy; tarefas só se ligam a projetos do próprio usuário.
- **Responsabilidade única em cada arquivo:** validação (Request), regra (Service),
  persistência (Repository), serialização (Resource), autorização (Policy).
- **DTO imutável com `array_filter`:** um único objeto serve create e update parcial sem
  efeitos colaterais.

### Mapa rápido "decisão → arquivo"

| Quero ver... | Vá em |
| --- | --- |
| Quais rotas existem e o que é protegido | `routes/api.php` |
| Como o token é validado | `config/auth.php` + middleware `auth:api` |
| Onde a validação acontece | `app/Http/Requests/**` |
| Onde está a regra de negócio | `app/Domain/**/Services` |
| Como os dados trafegam entre camadas | `app/Domain/**/DTOs` |
| Onde o banco é acessado | `app/Infrastructure/Persistence/Eloquent/**` |
| Quem amarra interface → implementação | `app/Providers/AppServiceProvider.php` |
| Como o JSON de saída é montado | `app/Http/Resources/**` |
| Regras de "quem pode acessar" | `app/Policies/**` |
| Esquema do banco e índices | `database/migrations/**` |

---

## 8. Módulo Financials e Unit of Work

O contexto `app/Domain/Financials` segue o mesmo fluxo das demais áreas:

```text
Route + Passport
  → FormRequest (validação + DTO)
  → Controller (autoriza Project ou Task)
  → Financial Service
  → RepositoryInterface / UnitOfWorkInterface
  ← EloquentRepository / EloquentUnitOfWork
  → Model
  → API Resource
```

### Responsabilidades

| Componente | Responsabilidade |
| --- | --- |
| `Money` | Converte strings decimais em unidades inteiras e impede aritmética com float |
| `ProjectService` | Impede a troca de moeda depois do início da atividade financeira |
| `FundService` | Lista e cria fundos somente em projetos com moeda configurada |
| `CostService` | Lista e registra custos em projetos com moeda, sem debitar fundos |
| `FinancialAllocationService` | Orquestra as invariantes e a alocação atômica |
| `UnitOfWorkInterface` | Contrato de transação conhecido pelo Domain |
| `EloquentUnitOfWork` | Implementa o contrato com uma transação do banco |
| `EloquentFundRepository` | Bloqueia o fundo e executa o débito condicional |

### Fluxo atômico da alocação

`FinancialAllocationService` abre a Unit of Work e carrega o fundo com
`lockForUpdate()`. Ainda dentro da transação, valida:

1. o usuário autenticado é dono da tarefa e do fundo;
2. o fundo pertence ao mesmo projeto da tarefa;
3. o projeto possui uma moeda configurada;
4. o saldo disponível cobre o valor solicitado.

Em seguida cria `FinancialAllocation` e executa um `UPDATE` condicionado a
`available_balance >= amount`. Se o update não afetar exatamente uma linha, ou qualquer
outra etapa lançar exceção, a transação é revertida. No PostgreSQL, o bloqueio de linha
serializa alocações concorrentes; o update condicionado funciona como defesa adicional
contra saldo negativo.

### Ciclo de vida da moeda do projeto

`projects.currency` guarda um código ISO 4217 em maiúsculas. Projetos novos devem enviar
uma moeda presente em `config/financial.php` (`FINANCIAL_CURRENCIES`). O campo permanece
nullable apenas para compatibilidade com projetos anteriores à migration; esses projetos
podem ser consultados e atualizados, mas não podem criar fundos, custos ou alocações até
o dono configurar a moeda. Depois que existir fundo ou custo, `ProjectService` rejeita
trocas de moeda. Os recursos financeiros derivam e expõem a moeda do projeto, sem
duplicá-la nas tabelas financeiras.

### Mapa do módulo

| Área | Arquivos |
| --- | --- |
| Domínio | `app/Domain/Financials/{Contracts,DTOs,Exceptions,Repositories,Services,ValueObjects}` |
| Persistência | `app/Infrastructure/Persistence/Eloquent/Eloquent{Fund,Cost,FinancialAllocation}Repository.php` |
| Unit of Work | `app/Infrastructure/Persistence/Eloquent/EloquentUnitOfWork.php` |
| HTTP | `app/Http/Controllers/Api/Project{Fund,Cost}Controller.php`, `TaskFinancialAllocationController.php` |
| Validação | `app/Http/Requests/Financials/**` |
| Serialização | `app/Http/Resources/{Project,Fund,Cost,FinancialAllocation}Resource.php` |
| Entidades | `app/Models/{Project,Fund,Cost,FinancialAllocation}.php` |
| Configuração | `config/financial.php` |
| Esquema | `database/migrations/*_add_currency_to_projects_table.php`, `database/migrations/*_{funds,costs,financial_allocations}_table.php` |
| Testes | `tests/Feature/{Project,Fund,Cost,FinancialAllocation}Test.php`, `tests/Unit/Domain/Projects/ProjectServiceTest.php`, `tests/Integration/FinancialAllocationConcurrencyTest.php` |
