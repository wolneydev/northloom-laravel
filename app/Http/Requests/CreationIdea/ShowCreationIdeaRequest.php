<?php

declare(strict_types=1);

namespace App\Http\Requests\CreationIdea;

use App\Domain\CreationIdeas\DTOs\CreationIdeaQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowCreationIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'target' => ['required', Rule::in(['project', 'task'])],
            'project_id' => [
                'required_if:target,task',
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('user_id', $this->user()?->id),
            ],
            'at' => ['nullable', 'date'],
        ];
    }

    public function toQuery(): CreationIdeaQuery
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CreationIdeaQuery::fromArray($validated);
    }
}
