<?php

declare(strict_types=1);

namespace App\Domain\Users\Services;

use App\Domain\Users\DTOs\TelegramSettingsData;
use App\Domain\Users\DTOs\UserData;
use App\Domain\Users\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Holds the business rules for managing users.
 *
 * Controllers stay thin by delegating here; data access stays behind the
 * repository contract. This keeps the rules testable in isolation.
 */
final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($perPage);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function find(int $id): User
    {
        return $this->users->findById($id)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$id]);
    }

    public function create(UserData $data): User
    {
        return $this->users->create($data);
    }

    public function update(int $id, UserData $data): User
    {
        $user = $this->find($id);

        return $this->users->update($user, $data);
    }

    public function updateTelegramSettings(User $user, TelegramSettingsData $data): User
    {
        return $this->users->updateTelegramSettings($user, $data);
    }

    public function delete(int $id): bool
    {
        $user = $this->find($id);

        return $this->users->delete($user);
    }
}
