<?php

use App\Mcp\Servers\HospitableServer;
use Laravel\Mcp\Facades\Mcp;

// Local (stdio) transport: MCP clients spawn `php artisan mcp:start hospitable`.
// Authentication is handled internally via the fixed USER_LOGIN/PASSWORD_USER
// service account (see App\Mcp\Concerns\AuthenticatesMcpUser) — there is no
// per-request login, so this server is not exposed over HTTP.
//
// The Northloom chat agent connects to this same handle via:
// Client::local('php', [base_path('artisan'), 'mcp:start', 'hospitable']).
Mcp::local('hospitable', HospitableServer::class);
