<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Users\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Thin HTTP boundary for the user resource.
 *
 * Validation lives in form requests, business rules in the service, and
 * serialization in the resource. The controller only wires them together.
 */
final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $users = $this->users->list($perPage);

        return UserResource::collection($users)->response();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->toData());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $user): JsonResponse
    {
        $foundUser = $this->users->find($user);

        return UserResource::make($foundUser)->response();
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $updatedUser = $this->users->update($user, $request->toData());

        return UserResource::make($updatedUser)->response();
    }

    public function destroy(int $user): JsonResponse
    {
        $this->users->delete($user);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
