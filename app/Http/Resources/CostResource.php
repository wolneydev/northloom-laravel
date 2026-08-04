<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Cost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cost */
final class CostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'currency' => $this->project->currency,
            'amount' => $this->amount,
            'description' => $this->description,
            'incurred_on' => $this->incurred_on?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
