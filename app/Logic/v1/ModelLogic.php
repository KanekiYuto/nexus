<?php

namespace App\Logic\v1;

use App\AIModels\ModelDispatch;
use App\Constants\GenerateTaskStatusConst;
use App\Constants\StatusCode;
use App\Jobs\GenerateSubmit;
use App\Jobs\TransferOutput;
use App\Models\TaskRecord;
use App\Models\TaskResult;
use App\Support\ApiResponse;
use App\Support\WebhookNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * 模型生成业务逻辑。
 *
 * 说明：
 * - generate() 负责幂等创建任务并投递异步队列
 * - webhook() 负责在生成完成后更新任务并回调业务侧地址
 */
class ModelLogic
{

    /**
     * 创建任务记录并投递到队列执行。
     *
     * @param array $params 经过控制器校验后的参数
     *
     * @return JsonResponse 统一 JSON 响应
     * @throws ValidationException
     */
    public static function generate(array $params): JsonResponse
    {
        // 校验模型专属参数，非法时抛 ValidationException，不创建任务记录
        ModelDispatch::validate($params['model'], $params['parameters']);

        // 延迟秒数兜底为 0，避免负数或空值
        $delaySeconds = max(0, (int)($params['delay_generation'] ?? 0));

        // 幂等检查：同一 app_id + custom_id 仅允许创建一次
        $existingTask = TaskRecord::query()
            ->where('app_id', $params['app_id'])
            ->where('custom_id', $params['custom_id'])
            ->first();

        if ($existingTask) {
            return ApiResponse::receipt((object)[
                'taskId' => $existingTask->id,
                'status' => $existingTask->status,
            ], 'Task has been created', StatusCode::WARN);
        }

        // 创建任务记录
        $taskRecord = TaskRecord::query()->create([
            'id' => (string)Str::ulid(),
            'app_id' => $params['app_id'],
            'custom_id' => $params['custom_id'],
            'model' => $params['model'],
            'status' => GenerateTaskStatusConst::IN_QUEUE,
            'requested_provider' => $params['provider'],
            'parameters' => $params['parameters'],
            'webhook_url' => $params['webhook_url'],
            'metadata' => $params['metadata'] ?? null,
        ]);

        if (!$taskRecord) {
            return ApiResponse::error('Failed to create task');
        }

        // 将任务交给队列异步执行，避免阻塞接口响应
        $job = GenerateSubmit::dispatch(
            $taskRecord->id,
            $params['custom_id'],
            $params['model'],
            $params['provider'],
            $params['parameters'],
            $params['webhook_url'],
            $params['fallback_provider'] ?? null,
        );

        // 支持按秒延迟执行
        if ($delaySeconds > 0) {
            $job->delay(now()->addSeconds($delaySeconds));
        }

        return ApiResponse::receipt((object)[
            'taskId' => $taskRecord->id,
            'status' => $taskRecord->status,
        ], 'Task submitted successfully');
    }

    /**
     * 处理服务商回调后的任务收敛。
     *
     * 流程：
     * - 根据 taskId 查询任务记录；不存在时记录告警并返回
     * - 将任务状态更新为 COMPLETED，并落库 provider_outputs/completed_at/duration_ms
     * - 调用业务侧 webhook_url 通知最终结果
     *
     * @param string $taskId 内部任务ID
     * @param array $outputs 服务商输出结果（通常为资源 URL 列表）
     *
     * @return void
     * @throws Throwable
     */
    public static function webhook(string $taskId, array $outputs): void
    {
        // 根据任务 ID 查询记录，优先走数据库状态收敛
        $taskRecord = TaskRecord::query()->where('id', $taskId)->first();
        $completedAt = time();
        $durationMs = max(0, ($completedAt - (int)$taskRecord->started_at) * 1000);

        if (empty($taskRecord)) {
            Log::warning('Task record not found when handling webhook', [
                'task_id' => $taskId,
            ]);
            return;
        }

        // 回写任务最终状态与输出
        $taskRecord->markCompleted($outputs, $completedAt, $durationMs);

        // 每个输出 URL 独立派发转存 Job，全部完成后推送 webhook
        $jobs = collect($outputs)
            ->map(fn(string $url, int $index) => new TransferOutput($taskRecord->id, $url, $index))
            ->values()
            ->all();

        $taskRecordId = $taskRecord->id;
        $customId = $taskRecord->custom_id;
        $webhookUrl = $taskRecord->webhook_url;

        Bus::batch($jobs)->then(function () use ($taskRecordId, $customId, $webhookUrl, $completedAt, $durationMs) {
            $storedUrls = TaskResult::query()
                ->where('task_record_id', $taskRecordId)
                ->orderBy('order_index')
                ->get()
                ->map(fn(TaskResult $r) => Storage::url($r->key))
                ->all();

            WebhookNotifier::resultUrlsUpdate($webhookUrl, $taskRecordId, $customId, $storedUrls);
        })->dispatch();
    }
}
