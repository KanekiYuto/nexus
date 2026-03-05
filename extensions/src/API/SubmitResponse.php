<?php

namespace Extensions\API;

use App\Constants\StatusCode;
use Throwable;

class SubmitResponse
{
    /**
     * 构造统一成功响应。
     *
     * @param string $providerId 上游任务ID
     * @param array $response 上游原始响应
     * @return array
     */
    public static function success(string $providerId, array $response): array
    {
        return [
            'success' => true,
            'code' => StatusCode::SUCCESS,
            'provider_id' => $providerId,
            'response' => $response,
        ];
    }

    /**
     * 构造统一失败响应。
     *
     * @param string $message 错误消息
     * @param array|null $response 上游原始响应
     * @return array
     */
    public static function fail(string $message, ?array $response = null): array
    {
        return [
            'success' => false,
            'code' => StatusCode::ERROR,
            'msg' => $message,
            'provider_id' => '',
            'response' => $response ?? [],
        ];
    }

    /**
     * 上游返回结构异常。
     *
     * @param string $providerName 服务商名称
     * @return array
     */
    public static function invalidPayload(string $providerName): array
    {
        return self::fail($providerName . ' returned an invalid response payload');
    }

    /**
     * 上游连接失败。
     *
     * @param string $providerName 服务商名称
     * @param Throwable $e 异常对象
     * @return array
     */
    public static function connectionFailed(string $providerName, Throwable $e): array
    {
        return self::fail('Unable to connect to ' . $providerName . ' service', [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ]);
    }
}
