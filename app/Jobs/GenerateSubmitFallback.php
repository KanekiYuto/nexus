<?php

namespace App\Jobs;

use App\Jobs\Concerns\SubmitsToProvider;
use App\Models\TaskRecord;
use App\Support\WebhookNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * 回退服务商提交 Job。
 *
 * 职责边界：
 * 1) 由 GenerateSubmit 在主服务商失败后自动派发
 * 2) 使用回退服务商提交生成任务
 * 3) 回写最终状态并向业务侧发送提交阶段回调
 *
 * 注意：任务此时已被 GenerateSubmit 标记为 IN_PROGRESS，
 * 本 Job 无需再次调用 markInProgress。
 */
class GenerateSubmitFallback implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use SubmitsToProvider;

    /**
     * @param string $taskRecordId 任务记录主键
     * @param string $customId     业务侧 ID
     * @param string $model        模型标识（如 bytedance/seedream/...）
     * @param string $provider     回退服务商标识
     * @param array  $parameters   提交给服务商的参数
     * @param string $webhookUrl   回调地址
     */
    public function __construct(
        public readonly string $taskRecordId,
        public readonly string $customId,
        public readonly string $model,
        public readonly string $provider,
        public readonly array  $parameters,
        public readonly string $webhookUrl,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $context = [
            'taskRecordId' => $this->taskRecordId,
            'startedAt'    => time(),
            'taskRecord'   => null,
            'callback'     => null,
        ];

        try {
            app(Pipeline::class)->send($context)->through([
                [$this, 'pipeLoadTaskRecord'],
                [$this, 'pipeSubmitFallback'],
                [$this, 'pipeSendCallback'],
            ])->thenReturn();
        } catch (Throwable $e) {
            $taskRecord = $context['taskRecord'] ?? null;
            if ($taskRecord instanceof TaskRecord) {
                $taskRecord->markFailedByException($this->provider, $e, (int)$context['startedAt']);
                WebhookNotifier::failed(
                    $this->webhookUrl,
                    $taskRecord->id,
                    $this->customId,
                    time(),
                    $e->getMessage() ?: 'Task fallback submit failed',
                );
            }
            throw $e;
        }
    }

    /**
     * 管道阶段：调用回退服务商提交并产出回调上下文。
     */
    public function pipeSubmitFallback(array $context, \Closure $next): array
    {
        /** @var TaskRecord $taskRecord */
        $taskRecord = $context['taskRecord'];
        $startedAt  = (int)$context['startedAt'];

        [$success, $payload] = $this->submitToProvider($this->provider, $this->taskRecordId);

        $taskRecord->finalizeAfterSubmit(
            provider:   $this->provider,
            success:    $success,
            payload:    $payload,
            startedAt:  $startedAt,
            providerId: $this->extractProviderId($payload),
        );

        $context['callback'] = [
            'taskId' => $taskRecord->id,
            'status' => $taskRecord->status,
            'error'  => $taskRecord->final_error_payload,
        ];

        return $next($context);
    }
}
