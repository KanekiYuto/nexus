<?php

namespace App\Http\Middleware;

use App\Constants\StatusCode;
use App\Models\AppToken;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 验证请求头中的 App Token。
 *
 * 客户端须在每次请求时携带：
 *   Authorization: Bearer <token>
 *
 * 验证逻辑：
 *   1. 在 app_token 表中匹配 value 字段
 *   2. Token 未过期（expired_at 为空或大于当前时间）
 *   3. 关联的 App 处于启用状态（status = 1）
 */
class AuthenticateAppToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->bearerToken();

        if (empty($value)) {
            return ApiResponse::basic('Unauthorized', StatusCode::UNAUTHORIZED);
        }

        $token = AppToken::with('app')->where('value', $value)->first();

        if (!$token || $token->isExpired() || !$token->app->isEnabled()) {
            return ApiResponse::basic('Unauthorized', StatusCode::UNAUTHORIZED);
        }

        // 将 app_id 注入请求 input，供下游控制器像普通参数一样读取
        $request->merge(['app_id' => $token->app_id]);

        return $next($request);
    }
}
