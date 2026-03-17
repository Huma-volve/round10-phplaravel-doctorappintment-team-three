<?php

use App\Http\Middleware\RoleMiddleware;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $message = $e->getMessage() ?: match ($status) {
                404 => 'Not found',
                401 => 'Unauthenticated',
                403 => 'Forbidden',
                422 => 'Validation error',
                default => 'Server error',
            };

            $errors = null;

            if (config('app.debug')) {
                $errors = [
                    'exception' => class_basename($e),
                ];
            }

            return ApiResponse::error($message, $status, $errors);
        });
    })->create();
