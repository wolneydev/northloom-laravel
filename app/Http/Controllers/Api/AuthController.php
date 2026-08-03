<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/**
 * Handles credential exchange for the API.
 *
 * On success a Passport access token (JWT) is issued; the protected routes
 * accept it through the `auth:api` guard.
 */
final class AuthController extends Controller
{
    #[OA\Post(
        path: '/login',
        summary: 'Log in and obtain an access token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid credentials or validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    /**
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('api')->accessToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => UserResource::make($user),
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Revoke the current access token',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->token()->revoke();

        return response()->json(['message' => __('Logged out successfully.')]);
    }
}
