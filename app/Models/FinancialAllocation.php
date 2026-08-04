<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FinancialAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'fund_id', 'amount', 'allocated_at'])]
class FinancialAllocation extends Model
{
    /** @use HasFactory<FinancialAllocationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'allocated_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
