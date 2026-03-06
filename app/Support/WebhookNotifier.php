<?php

namespace App\Support;

use App\Constants\GenerateTaskStatusConst;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 业务侧 webhook 通知器。
 *
 * 设计约定：
 * - 仅负责发送通知，不参与任务状态计算
 * - 回调失败只记录日志，不抛异常影响主流程
 */
class WebhookNotifier
{
    /**
     * 发送“任务已入队”通知。
     *
     * @param string $webhookUrl 业务侧回调地址
     * @param string $taskId     内部任务 ID
     * @param string $customId   业务侧任务 ID
     *
     * @return void
     */
    public static function inQueue(string $webhookUrl, string $taskId, string $customId): void
    {
        if (empty($webhookUrl)) {
            return;
        }

        self::http($webhookUrl, [
            'status' => GenerateTaskStatusConst::IN_QUEUE,
            'task_id' => $taskId,
            'custom_id' => $customId,
        ]);
    }

    /**
     * 发送“任务失败”通知。
     *
     * @param string $webhookUrl  业务侧回调地址
     * @param string $taskId      内部任务 ID
     * @param string $customId    业务侧任务 ID
     * @param int    $completedAt 完成时间（秒级时间戳）
     * @param string $error       错误描述
     *
     * @return void
     */
    public static function failed(string $webhookUrl, string $taskId, string $customId, int $completedAt, string $error): void
    {
        if (empty($webhookUrl)) {
            return;
        }

        self::http($webhookUrl, [
            'status' => GenerateTaskStatusConst::FAILED,
            'task_id' => $taskId,
            'custom_id' => $customId,
            'completed_at' => $completedAt,
            'error' => $error,
        ]);
    }

    /**
     * 发送结果 URL 更新通知
     *
     * @param string $webhookUrl
     * @param string $taskId
     * @param string $customId
     * @param array  $resultUrls
     *
     * @return void
     */
    public static function resultUrlsUpdate(string $webhookUrl, string $taskId, string $customId, array $resultUrls): void
    {
        if (empty($webhookUrl)) {
            return;
        }

        self::http($webhookUrl, [
            'status' => GenerateTaskStatusConst::RESULT_URLS_UPDATE,
            'task_id' => $taskId,
            'custom_id' => $customId,
            'result_urls' => $resultUrls,
        ]);
    }

    /**
     * 发送“任务完成”通知。
     *
     * @param string $webhookUrl  业务侧回调地址
     * @param string $taskId      内部任务 ID
     * @param string $customId    业务侧任务 ID
     * @param int    $completedAt 完成时间（秒级时间戳）
     * @param int    $durationMs  耗时（毫秒）
     * @param array  $result      任务输出结果
     *
     * @return void
     */
    public static function completed(
        string $webhookUrl,
        string $taskId,
        string $customId,
        int    $completedAt,
        int    $durationMs,
        array  $result,
    ): void {
        if (empty($webhookUrl)) {
            return;
        }

        self::http($webhookUrl, [
            'status' => GenerateTaskStatusConst::COMPLETED,
            'task_id' => $taskId,
            'custom_id' => $customId,
            'result' => $result,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * 执行实际 HTTP 回调请求。
     *
     * 说明：
     * - 失败时仅写 warning 日志，调用方无需显式捕获
     *
     * @param string $url    回调地址
     * @param array  $params 回调载荷
     *
     * @return void
     */
    private static function http(string $url, array $params): void
    {
        try {
            Http::acceptJson()->post($url, $params);
        } catch (Throwable $e) {
            Log::warning('Webhook callback failed', array_merge($params, [
                'url' => $url,
                'error' => $e->getMessage(),
            ]));
        }
    }
}
