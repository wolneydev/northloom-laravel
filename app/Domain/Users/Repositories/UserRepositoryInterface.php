<?php

declare(strict_types=1);

namespace App\Domain\Users\Repositories;

use App\Domain\Users\DTOs\TelegramSettingsData;
use App\Domain\Users\DTOs\UserData;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Abstraction over user persistence.
 *
 * The domain depends on this contract rather than Eloquent directly, so the
 * storage mechanism can change without touching business rules or controllers.
 */
interface UserRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(UserData $data): User;

    public function update(User $user, UserData $data): User;

    public function updateTelegramSettings(User $user, TelegramSettingsData $data): User;

    public function delete(User $user): bool;
}
