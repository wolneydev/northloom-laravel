<?php

use App\Mcp\Servers\HospitableServer;
use Laravel\Mcp\Facades\Mcp;

// Local (stdio) transport: MCP clients spawn `php artisan mcp:start hospitable`.
// Authentication is handled internally via the fixed USER_LOGIN/PASSWORD_USER
// service account (see App\Mcp\Concerns\AuthenticatesMcpUser) — there is no
// per-request login, so this server is not exposed over HTTP.
Mcp::local('hospitable', HospitableServer::class);
