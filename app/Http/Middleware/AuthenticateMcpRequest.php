<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates HTTP access to the web-exposed MCP server with a fixed bearer token
 * (MCP_WEB_TOKEN). This only protects the transport; tools still act on
 * behalf of the fixed service account via App\Mcp\Concerns\AuthenticatesMcpUser.
 */
class AuthenticateMcpRequest
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.mcp.web_token', '');
        $providedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
