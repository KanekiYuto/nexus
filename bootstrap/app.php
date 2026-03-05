<?php

use App\Constants\StatusCode;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\SetClientIpFromFastly;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request as RequestAlias;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    // 不包含 HEADER_X_FORWARDED_FOR：真实客户端 IP 已由 SetClientIpFromFastly 写入
    // REMOTE_ADDR，Symfony 无需再从 X-Forwarded-For 推断，避免覆盖导致结果错误。
    // 保留 HOST / PORT / PROTO 用于正确解析请求协议（HTTPS）和域名。
    $middleware->trustProxies(
        at: '*',
        headers: RequestAlias::HEADER_X_FORWARDED_HOST |
        RequestAlias::HEADER_X_FORWARDED_PORT |
        RequestAlias::HEADER_X_FORWARDED_PROTO
    );

    // Fastly 在边缘处将真实客户端 IP 写入 fastly-client-ip，不可伪造
    // 必须在 trustProxies 之前执行，让后续中间件获得正确的 REMOTE_ADDR
    $middleware->prepend(SetClientIpFromFastly::class);

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

        return ApiResponse::basic('Not Found', StatusCode::NOT_FOUND, [
            'ip' => request()->ip(),
        ]);
    });

    // 验证错误
    $exceptions->render(function (ValidationException $e) {
        return ApiResponse::basic(
            $e->getMessage(),
            StatusCode::VALIDATION_ERROR,
            [
                'receipt' => (object)[
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
