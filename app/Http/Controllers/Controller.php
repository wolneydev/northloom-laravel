<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API for managing projects, calendar tasks, funds, costs and financial allocations. Authentication uses a Laravel Passport bearer access token.',
    title: 'Hospitable API',
)]
#[OA\Server(
    url: '/api',
    description: 'API server',
)]
#[OA\Tag(name: 'Auth', description: 'Login and logout')]
#[OA\Tag(name: 'Users', description: 'User sign-up')]
#[OA\Tag(name: 'Projects', description: 'Project planning')]
#[OA\Tag(name: 'Tasks', description: 'Calendar tasks scoped to the authenticated user')]
#[OA\Tag(name: 'Funds', description: 'Project funding sources')]
#[OA\Tag(name: 'Costs', description: 'Project costs')]
#[OA\Tag(name: 'Financial Allocations', description: 'Allocation of fund money to tasks')]
#[OA\Tag(name: 'Reports', description: 'Aggregated project and task reports')]
#[OA\Tag(name: 'Telegram', description: "Authenticated user's Telegram notification preferences")]
abstract class Controller
{
    //
}
