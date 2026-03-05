<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * 注册 Telescope 相关服务。
     *
     * 启用夜间主题，屏蔽敏感字段，并配置条目过滤规则：
     * - 本地环境：记录所有条目
     * - 非本地环境：仅记录异常、失败请求、失败队列任务、计划任务及带监控标签的条目
     */
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag();
        });
    }

    /**
     * 屏蔽请求中的敏感信息，避免被 Telescope 持久化记录。
     *
     * 本地环境不做任何屏蔽，方便调试。
     * 非本地环境屏蔽 CSRF Token 参数及认证相关请求头。
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * 注册 Telescope 访问授权 Gate。
     *
     * 本地环境不受限制，任何人均可访问。
     * 非本地环境要求用户已通过 /admin/login 完成登录认证。
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (?User $user) {
            if ($this->app->environment('local')) {
                return true;
            }

            // 通过 /admin/login 登录后的任意已认证用户均可访问
            return $user !== null;
        });
    }
}
