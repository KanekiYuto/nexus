<?php

namespace App\Http\Controllers\v1;

use App\Support\ApiResponse;
use App\Support\Token;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;

/**
 * 令牌接口控制器。
 */
class TokenController
{

    /**
     * 签发访问令牌。
     *
     * @param Request $request 请求参数
     * @return JsonResponse 统一 JSON 响应
     */
    public function issue(Request $request): JsonResponse
    {
        $requestParams = $request::validate([
            'ttl' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        $token = Token::issue($requestParams['ttl'] ?? 300);

        return ApiResponse::receipt([
            'token'      => $token,
            'expires_in' => $requestParams['ttl'] ?? 300,
        ]);
    }

}
