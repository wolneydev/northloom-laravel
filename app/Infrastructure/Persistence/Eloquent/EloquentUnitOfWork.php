<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Financials\Contracts\UnitOfWorkInterface;
use Illuminate\Support\Facades\DB;

final class EloquentUnitOfWork implements UnitOfWorkInterface
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
