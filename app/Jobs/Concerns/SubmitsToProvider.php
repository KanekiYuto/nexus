<?php

namespace App\Jobs\Concerns;

use App\AIModels\ModelDispatch;
use App\Constants\GenerateTaskStatusConst;
use App\Models\TaskRecord;
use App\Support\WebhookNotifier;
use Closure;
use Extensions\API\Exceptions\ProviderSubmitException;

/**
 * 服务商提交共享 Trait。
 *
 * 供 GenerateSubmit / GenerateSubmitFallback 复用的管道阶段与辅助方法。
 *
 * 使用该 Trait 的类须声明以下属性：
 * - string $taskRecordId
 * - string $customId
 * - string $model
 * - string $provider
 * - array $parameters
 * - string $webhookUrl
 */
trait SubmitsToProvider
{
    /**
     * 管道阶段：加载任务记录。
     *
     * 任务不存在时直接发送失败回调并终止后续阶段（不调用 $next）。
     */
    public function pipeLoadTaskRecord(array $context, Closure $next): array
    {
        $taskRecord = TaskRecord::query()->find((string)$context['taskRecordId']);

        if (!$taskRecord) {
            WebhookNotifier::failed(
                $this->webhookUrl,
                (string)$context['taskRecordId'],
                $this->customId,
                time(),
                'Task record not found',
            );
            return $context;
        }

        $context['taskRecord'] = $taskRecord;
        return $next($context);
    }

    /**
     * 管道阶段：发送任务回调通知。
     *
     * 依据 $context['callback']['status'] 选择发送 inQueue 或 failed 回调。
     * 若 callback 未写入上下文（如被上游阶段终止），则跳过不发送。
     */
    public function pipeSendCallback(array $context, Closure $next): array
    {
        $callback = $context['callback'] ?? null;
        if (!is_array($callback)) {
            return $next($context);
        }

        $taskId = (string)$callback['taskId'];
        $status = (string)$callback['status'];

        if ($status === GenerateTaskStatusConst::FAILED) {
            WebhookNotifier::failed(
                $this->webhookUrl,
                $taskId,
                $this->customId,
                time(),
                $this->resolveErrorMessage($callback['error'] ?? null),
            );
        } else {
            WebhookNotifier::inQueue($this->webhookUrl, $taskId, $this->customId);
        }

        return $next($context);
    }

    /**
     * 调用服务商 submit，成功返回响应数组，失败抛出 ProviderSubmitException。
     *
     * @param string $provider 服务商标识
     * @param string $taskId   任务 ID（传给服务商用于回调路由）
     *
     * @return array 成功响应（provider_id / response）
     *
     * @throws ProviderSubmitException
     */
    protected function submitToProvider(string $provider, string $taskId): array
    {
        return ModelDispatch::submit($this->model, $provider, $this->parameters, $taskId);
    }

    /**
     * 从 submit 响应中提取服务商任务 ID。
     */
    protected function extractProviderId(array $payload): string
    {
        return (string)($payload['provider_id'] ?? '');
    }

    /**
     * 从错误载荷中提取可读错误消息。
     */
    private function resolveErrorMessage(mixed $error): string
    {
        if (is_array($error)) {
            return (string)($error['msg'] ?? $error['message'] ?? 'Submit failed');
        }
        return (string)($error ?: 'Submit failed');
    }
}
