<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 从 Fastly CDN 注入的请求头中提取真实客户端 IP。
 *
 * 背景：
 *   Railway 部署的服务流量经由 Fastly CDN 转发，链路为：
 *   用户浏览器 → Fastly 边缘节点（多级） → Railway 容器
 *
 *   Fastly 在最外层入口（最接近用户一侧）写入 fastly-client-ip，
 *   其值等于用户的真实出口 IP，且不可被客户端在请求中预先伪造
 *   （Fastly 会丢弃客户端传入的同名头）。
 *
 *   相比之下：
 *   - X-Forwarded-For 包含完整链路（用户 + 各级 Fastly 节点），
 *     Laravel 的 trustProxies(at: '*') 只信任最后一跳，无法可靠地取到真实用户 IP。
 *   - X-Real-IP 是最后一跳 Fastly 边缘节点的 IP，并非用户 IP。
 *
 * 作用：
 *   通过 prepend 在所有中间件之前执行，将 REMOTE_ADDR 覆写为 fastly-client-ip，
 *   使 $request->ip()、Telescope、日志、频率限制等所有依赖 REMOTE_ADDR 的
 *   组件都能获得正确的用户真实 IP。
 *
 * 注意：
 *   仅在经由 Fastly 代理的环境（生产/预发布）下生效；
 *   本地开发时请求头不存在，逻辑会直接跳过，不影响正常使用。
 */
class SetClientIpFromFastly
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($clientIp = $request->header('fastly-client-ip')) {
            $request->server->set('REMOTE_ADDR', $clientIp);
        }

        return $next($request);
    }
}
