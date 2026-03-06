<?php

namespace Extensions\API;

use Extensions\API\Exceptions\ProviderSubmitException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class WaveSpeed
{

    /**
     * 基础 URL
     *
     * @var string
     */
    protected static string $baseUrl = 'https://api.wavespeed.ai/api/v3';

    /**
     * 向 WaveSpeed 提交图像生成任务。
     *
     * @param string               $model  上游模型路径，例如 "bytedance/seedream-v4.5"
     * @param array<string, mixed> $params 提交给上游服务的请求参数
     * @param string               $taskId 任务 ID
     *
     * @return array 成功响应（provider_id / response）
     *
     * @throws ProviderSubmitException 连接失败或服务商返回错误时抛出
     */
    public static function submit(string $model, array $params, string $taskId): array
    {
        try {
            $webhookBaseUrl = config('app.url');
            $apiKey = config('services.wavespeed.key');

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->withQueryParameters([
                    'webhook' => "$webhookBaseUrl/api/v1/model/webhook/wavespeed/$taskId",
                ])
                ->post(self::$baseUrl . '/' . $model, $params)
                ->json();

            if (!is_array($response)) {
                throw new ProviderSubmitException('WaveSpeed returned an invalid response payload');
            }

            if (!isset($response['code']) || (int)$response['code'] !== 200) {
                throw new ProviderSubmitException(
                    (string)($response['message'] ?? 'WaveSpeed submit failed'),
                    $response,
                );
            }

            return SubmitResponse::success((string)($response['data']['id'] ?? ''), $response);
        } catch (ConnectionException $e) {
            Log::error($e->getMessage());
            throw new ProviderSubmitException(
                'Unable to connect to WaveSpeed service',
                ['message' => $e->getMessage(), 'code' => $e->getCode()],
                $e,
            );
        }
    }

}
