<?php

use App\Constants\StatusCode;
use App\Http\Middleware\Authenticate;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

return Application::configure(basePath: dirname(__DIR__))->withRouting(
    using: function () {
        Route::name('api.')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Route::middleware(['web'])
            ->group(base_path('routes/web.php'));
    },
    commands: __DIR__ . '/../routes/console.php',
    channels: __DIR__ . '/../routes/channels.php',
)->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'auth' => Authenticate::class,
    ]);

    $middleware->trustProxies(
        at: '*',
        headers: RequestAlias::HEADER_X_FORWARDED_FOR |
            RequestAlias::HEADER_X_FORWARDED_HOST |
            RequestAlias::HEADER_X_FORWARDED_PORT |
            RequestAlias::HEADER_X_FORWARDED_PROTO
    );

    $middleware->validateCsrfTokens(except: [
        'telescope/*',
    ]);
})->withExceptions(function (Exceptions $exceptions) {
    // 请求方法不允许
    $exceptions->render(function (MethodNotAllowedHttpException $e) {
        Log::error($e->getMessage());

        return ApiResponse::basic(
            'Method Not Allowed',
            StatusCode::METHOD_NOT_ALLOWED
        );
    });

    // 路由不存在
    $exceptions->render(function (NotFoundHttpException $e) {
        Log::error($e->getMessage());

        return ApiResponse::basic(
            'Not Found',
            StatusCode::NOT_FOUND
        );
    });

    // 验证错误
    $exceptions->render(function (ValidationException $e) {
        return ApiResponse::basic(
            $e->getMessage(),
            StatusCode::VALIDATION_ERROR,
            [
                'receipt' => (object) [
                    'errors' => $e->errors(),
                ],
            ]
        );
    });

    // Http 异常
    $exceptions->render(function (HttpException $e) {
        return match ($e->getStatusCode()) {
            StatusCode::SERVICE_UNAVAILABLE => ApiResponse::basic(
                'Service Unavailable',
                StatusCode::SERVICE_UNAVAILABLE
            ),
            StatusCode::FORBIDDEN => ApiResponse::basic(
                'Forbidden',
                StatusCode::FORBIDDEN
            ),
            StatusCode::UNAUTHORIZED => ApiResponse::basic(
                'Unauthorized',
                StatusCode::UNAUTHORIZED,
                [
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                    $e->getCode(),
                    get_class($e),
                ]
            ),
            default => ApiResponse::basic(
                $e->getMessage(),
                StatusCode::ERROR
            ),
        };
    });

    // 其他错误
    $exceptions->render(function (Exception $e) {
        return ApiResponse::rows(
            [
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString(),
                $e->getCode(),
                get_class($e),
            ],
            $e->getMessage(),
            StatusCode::ERROR
        );
    });
})->create();
