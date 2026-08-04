<?php

declare(strict_types=1);

namespace App\Mcp\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Resolves the acting user for MCP tools.
 *
 * The MCP server has no login or sign-up flow of its own; every tool acts on
 * behalf of the fixed service account configured via `services.mcp` (backed
 * by the USER_LOGIN/PASSWORD_USER .env values).
 */
trait AuthenticatesMcpUser
{
    protected function resolveActingUser(): User
    {
        $email = trim((string) config('services.mcp.user_login', ''));
        $password = (string) config('services.mcp.user_password', '');

        if ($email === '' || $password === '') {
            throw ValidationException::withMessages([
                'mcp_user' => ['USER_LOGIN e PASSWORD_USER precisam estar configurados no .env para usar este servidor MCP.'],
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'mcp_user' => ['Não foi possível autenticar o usuário de serviço do MCP. Verifique USER_LOGIN e PASSWORD_USER no .env.'],
            ]);
        }

        return $user;
    }
}
