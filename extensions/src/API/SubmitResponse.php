<?php

namespace Extensions\API;

class SubmitResponse
{
    /**
     * 构造统一成功响应。
     *
     * @param string $providerId 上游任务 ID
     * @param array $response 上游原始响应
     * @return array
     */
    public static function success(string $providerId, array $response): array
    {
        return [
            'provider_id' => $providerId,
            'response'    => $response,
        ];
    }
}
