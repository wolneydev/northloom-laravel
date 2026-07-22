<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\Users\DTOs\UserData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function toData(): UserData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UserData::fromArray($validated);
    }
}
