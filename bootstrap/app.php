<?php

use App\Domain\Financials\Exceptions\FinancialOwnershipException;
use App\Domain\Financials\Exceptions\FundProjectMismatchException;
use App\Domain\Financials\Exceptions\InsufficientFundBalanceException;
use App\Domain\Financials\Exceptions\ProjectCurrencyNotConfiguredException;
use App\Domain\Projects\Exceptions\ProjectCurrencyImmutableException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (FinancialOwnershipException $exception, Request $request) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        });
        $exceptions->render(function (FundProjectMismatchException $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['fund_id' => [$exception->getMessage()]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
        $exceptions->render(function (InsufficientFundBalanceException $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['amount' => [$exception->getMessage()]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
        $exceptions->render(function (
            ProjectCurrencyNotConfiguredException|ProjectCurrencyImmutableException $exception,
            Request $request,
        ) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['currency' => [$exception->getMessage()]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
