<?php

namespace App\Providers;

use App\Domain\Financials\Contracts\UnitOfWorkInterface;
use App\Domain\Financials\Repositories\CostRepositoryInterface;
use App\Domain\Financials\Repositories\FinancialAllocationRepositoryInterface;
use App\Domain\Financials\Repositories\FundRepositoryInterface;
use App\Domain\Notifications\Contracts\TelegramNotificationServiceInterface;
use App\Domain\Projects\Repositories\ProjectRepositoryInterface;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Domain\Users\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\EloquentCostRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentFinancialAllocationRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentFundRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentProjectRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTaskRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUnitOfWork;
use App\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use App\Infrastructure\Telegram\TelegramNotificationService;
use App\Models\Project;
use App\Models\Task;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bind domain contracts to their concrete implementations so the rest of
     * the application can depend on abstractions.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
        ProjectRepositoryInterface::class => EloquentProjectRepository::class,
        TaskRepositoryInterface::class => EloquentTaskRepository::class,
        FundRepositoryInterface::class => EloquentFundRepository::class,
        CostRepositoryInterface::class => EloquentCostRepository::class,
        FinancialAllocationRepositoryInterface::class => EloquentFinancialAllocationRepository::class,
        UnitOfWorkInterface::class => EloquentUnitOfWork::class,
        TelegramNotificationServiceInterface::class => TelegramNotificationService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
