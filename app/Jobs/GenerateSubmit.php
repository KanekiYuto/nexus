<?php

namespace App\Jobs;

use App\Jobs\Concerns\SubmitsToProvider;
use App\Models\TaskRecord;
use App\Support\WebhookNotifier;
use Closure;
use Extensions\API\Exceptions\ProviderSubmitException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * 主服务商提交 Job。
 *
 * 职责边界：
 * 1) 标记任务为执行中
 * 2) 调用主服务商提交生成任务
 * 3) 主服务商失败且配置了回退时，派发 GenerateSubmitFallback（回调由该 Job 负责）
 * 4) 无回退时回写最终状态并向业务侧发送提交阶段回调
 */
class GenerateSubmit implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use SubmitsToProvider;

    /**
     * @param string      $taskRecordId    任务记录主键
     * @param string      $customId        业务侧 ID
     * @param string      $model           模型标识（如 bytedance/seedream/...）
     * @param string      $provider        主服务商标识
     * @param array       $parameters      提交给服务商的参数
     * @param string      $webhookUrl      回调地址
     * @param string|null $fallbackProvider 回退服务商（可空）
     */
    public function __construct(
        public readonly string  $taskRecordId,
        public readonly string  $customId,
        public readonly string  $model,
        public readonly string  $provider,
        public readonly array   $parameters,
        public readonly string  $webhookUrl,
        public readonly ?string $fallbackProvider = null,
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
                [$this, 'pipeMarkTaskInProgress'],
                [$this, 'pipeSubmitPrimary'],
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
                    $e->getMessage() ?: 'Task submit failed',
                );
            }
            throw $e;
        }
    }

    /**
     * 管道阶段：标记任务进入执行中状态。
     */
    public function pipeMarkTaskInProgress(array $context, Closure $next): array
    {
        /** @var TaskRecord $taskRecord */
        $taskRecord = $context['taskRecord'];
        $taskRecord->markInProgress((int)$context['startedAt']);
        return $next($context);
    }

    /**
     * 管道阶段：调用主服务商提交。
     *
     * - 提交成功 → 收敛状态，写入回调上下文，继续管道
     * - 提交失败且有回退 → 记录主服务商错误，派发 GenerateSubmitFallback，终止管道
     *   （后续回调由 GenerateSubmitFallback 负责，此 Job 不再发送 webhook）
     * - 提交失败且无回退 → 收敛失败状态，写入回调上下文，继续管道
     */
    public function pipeSubmitPrimary(array $context, Closure $next): array
    {
        /** @var TaskRecord $taskRecord */
        $taskRecord = $context['taskRecord'];
        $startedAt  = (int)$context['startedAt'];

        try {
            $payload = $this->submitToProvider($this->provider, $this->taskRecordId);
            $taskRecord->markSubmitted($this->provider, $this->extractProviderId($payload));
        } catch (ProviderSubmitException $e) {
            if ($this->canUseFallback()) {
                $taskRecord->markFallbackTriggered($e->payload);
                GenerateSubmitFallback::dispatch(
                    $this->taskRecordId,
                    $this->customId,
                    $this->model,
                    (string)$this->fallbackProvider,
                    $this->parameters,
                    $this->webhookUrl,
                );
                // 终止管道，不发送本次 webhook
                return $context;
            }

            $taskRecord->markSubmitFailed($this->provider, $e->payload, $startedAt);
        }

        $context['callback'] = [
            'taskId' => $taskRecord->id,
            'status' => $taskRecord->status,
            'error'  => $taskRecord->final_error_payload,
        ];

        return $next($context);
    }

    /**
     * 判断是否可用回退服务商。
     */
    private function canUseFallback(): bool
    {
        return !empty($this->fallbackProvider) && $this->fallbackProvider !== $this->provider;
    }
}
