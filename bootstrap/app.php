<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ResolveGovernanceUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'governance.auth' => ResolveGovernanceUser::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'Datos inválidos. Revisa los campos marcados.',
                'data' => null,
                'errors' => $e->errors(),
                'error_code' => 'VAL01',
                'meta' => [
                    'request_id' => $request->headers->get('X-Request-ID') ?? uniqid('req_'),
                    'api_version' => 'v1',
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 422);
        });
    })->create();
