<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sentinel\Drivers\Driver;
use Laravel\Sentinel\Drivers\Laravel as SentinelLaravelDriver;
use Laravel\Sentinel\Sentinel;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 为 Telescope 注册 Sentinel 专用驱动：
        // - 本地环境：直接放行
        // - 非本地环境：走 Sentinel 默认 Laravel 驱动校验
        Sentinel::extend('telescope', function ($app) {
            return new class(fn () => $app) extends Driver {
                public function authorize(Request $request): bool
                {
                    if ($this->app()->environment('local')) {
                        return true;
                    }

                    return (new SentinelLaravelDriver(fn () => $this->app()))->authorize($request);
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 本地环境下移除 Sentinel 对 Telescope 的拦截，仅保留 Telescope 自身授权中间件。
        // 线上环境不改，仍走默认中间件链路。
        if ($this->app->environment('local')) {
            Route::middlewareGroup('telescope', config('telescope.middleware', ['web']));
        }
    }
}
