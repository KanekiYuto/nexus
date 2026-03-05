<?php

namespace Extensions\API;

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
     * - 成功时返回上游回执（receipt）。
     * - 失败时返回统一的业务错误响应结构。
     *
     * @param string $model 上游模型路径，例如 "bytedance/seedream-v4.5"
     * @param array<string, mixed> $params 提交给上游服务的请求参数
     * @param string $taskId 任务 ID
     *
     * @return array 响应
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
                return SubmitResponse::invalidPayload('WaveSpeed');
            }

            if (!isset($response['code']) || (int)$response['code'] !== 200) {
                return SubmitResponse::fail((string)($response['message'] ?? 'WaveSpeed submit failed'), $response);
            }

            return SubmitResponse::success((string)($response['data']['id'] ?? ''), $response);
        } catch (ConnectionException $e) {
            Log::error($e->getMessage());
            return SubmitResponse::connectionFailed('WaveSpeed', $e);
        }
    }

}
