<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Users\DTOs\TelegramSettingsData;
use App\Domain\Users\DTOs\UserData;
use App\Domain\Users\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent-backed implementation of the user repository contract.
 *
 * This is the only place that knows about Eloquent for users, isolating the
 * framework's persistence details from the domain.
 */
final class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function create(UserData $data): User
    {
        return User::query()->create($data->toArray());
    }

    public function update(User $user, UserData $data): User
    {
        $user->fill($data->toArray());
        $user->save();

        return $user->refresh();
    }

    public function updateTelegramSettings(User $user, TelegramSettingsData $data): User
    {
        $user->forceFill($data->toArray());
        $user->save();

        return $user->refresh();
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
