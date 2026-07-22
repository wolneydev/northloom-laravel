<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\Users\DTOs\UserData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function toData(): UserData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UserData::fromArray($validated);
    }
}
