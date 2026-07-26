<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'name', 'opening_balance', 'available_balance'])]
class Fund extends Model
{
    /** @use HasFactory<FundFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'available_balance' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function financialAllocations(): HasMany
    {
        return $this->hasMany(FinancialAllocation::class);
    }
}
