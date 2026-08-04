<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FinancialAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialAllocation */
final class FinancialAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'fund_id' => $this->fund_id,
            'currency' => $this->fund->project->currency,
            'amount' => $this->amount,
            'allocated_at' => $this->allocated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
