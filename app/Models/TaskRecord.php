<?php

namespace App\Models;

use App\Constants\GenerateTaskStatusConst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

/**
 * @property string $id
 * @property string $app_id
 * @property string $custom_id
 * @property string $model
 * @property string $status
 * @property string $webhook_url
 * @property array $provider_outputs
 * @property string $requested_provider
 * @property string|null $requested_provider_task_id
 * @property string|null $fallback_provider
 * @property string|null $fallback_provider_task_id
 * @property bool $fallback_used
 * @property string|null $final_provider
 * @property array $parameters
 * @property array|null $metadata
 * @property array|null $primary_error_payload
 * @property array|null $final_error_payload
 * @property int $duration_ms
 * @property int|null $started_at
 * @property int|null $completed_at
 * @property int $created_at
 * @property int $updated_at
 */
class TaskRecord extends Model
{
    /**
     * 任务记录表名。
     *
     * @var string
     */
    protected $table = 'task_record';

    /**
     * 主键为 ULID，非自增。
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * ULID 使用字符串主键类型。
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 当前表使用的是整型时间戳字段，关闭 Eloquent 默认时间戳写入逻辑。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值字段。
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'app_id',
        'custom_id',
        'model',
        'status',
        'webhook_url',
        'provider_outputs',
        'requested_provider',
        'requested_provider_task_id',
        'fallback_provider',
        'fallback_provider_task_id',
        'fallback_used',
        'final_provider',
        'parameters',
        'metadata',
        'primary_error_payload',
        'final_error_payload',
        'duration_ms',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fallback_used' => 'boolean',
            'parameters' => 'array',
            'metadata' => 'array',
            'primary_error_payload' => 'array',
            'final_error_payload' => 'array',
            'provider_outputs' => 'array',
            'duration_ms' => 'integer',
            'started_at' => 'integer',
            'completed_at' => 'integer',
            'created_at' => 'integer',
            'updated_at' => 'integer',
        ];
    }

    /**
     * 模型生命周期事件。
     * 在创建时补齐默认时间字段，避免在业务层重复赋值。
     */
    protected static function booted(): void
    {
        static::creating(function (TaskRecord $taskRecord) {
            $now = time();

            $taskRecord->started_at ??= $now;
            $taskRecord->created_at ??= $now;
            $taskRecord->updated_at ??= $now;
        });
    }

    /**
     * 标记任务进入执行中状态。
     *
     * @param int $startedAt 开始时间（秒级时间戳）
     * @return void
     */
    public function markInProgress(int $startedAt): void
    {
        $this->update([
            'status' => GenerateTaskStatusConst::IN_PROGRESS,
            'updated_at' => $startedAt,
        ]);
    }

    /**
     * 标记触发回退，并记录主服务商错误。
     *
     * @param array $primaryPayload 主服务商响应
     * @return void
     */
    public function markFallbackTriggered(array $primaryPayload): void
    {
        $this->update([
            'fallback_used' => true,
            'primary_error_payload' => $primaryPayload,
            'updated_at' => time(),
        ]);
    }

    /**
     * submit 后统一回写任务状态。
     * submit 成功仅表示进入上游队列，非最终完成。
     *
     * @param string $provider 实际提交使用的服务商
     * @param bool $success 提交是否成功
     * @param array $payload 提交响应
     * @param int $startedAt 开始时间（秒级时间戳）
     * @param string $providerId 服务商任务 ID
     * @return void
     */
    public function finalizeAfterSubmit(
        string $provider,
        bool   $success,
        array  $payload,
        int    $startedAt,
        string $providerId
    ): void
    {
        $now = time();

        $updateData = [
            'status' => $success ? GenerateTaskStatusConst::IN_QUEUE : GenerateTaskStatusConst::FAILED,
            'final_provider' => $provider,
            'final_error_payload' => $success ? null : $payload,
            'updated_at' => $now,
            'requested_provider_task_id' => $providerId,
        ];

        if (!$success) {
            $updateData['completed_at'] = $now;
            $updateData['duration_ms'] = max(0, ($now - (int)$this->started_at) * 1000);
            // started_at 为空时，退化为使用入参 startedAt 计算，保持兼容
            if ((int)$this->started_at <= 0) {
                $updateData['duration_ms'] = max(0, ($now - $startedAt) * 1000);
            }
        }

        $this->update($updateData);
    }

    /**
     * 将任务标记为异常失败。
     *
     * @param string $provider 最终服务商
     * @param Throwable $e 捕获到的异常
     * @param int $startedAt 开始时间（秒级时间戳）
     * @return void
     */
    public function markFailedByException(string $provider, Throwable $e, int $startedAt): void
    {
        $now = time();

        $this->update([
            'status' => GenerateTaskStatusConst::FAILED,
            'final_provider' => $provider,
            'final_error_payload' => [
                'error' => $e->getMessage(),
            ],
            'completed_at' => $now,
            'duration_ms' => max(0, ($now - $startedAt) * 1000),
            'updated_at' => $now,
        ]);
    }

    /**
     * 标记任务完成并写入输出结果。
     *
     * @param array $outputs 输出结果
     * @param int $completedAt 完成时间（秒级时间戳）
     * @param int $durationMs 完成耗时
     *
     * @return void
     */
    public function markCompleted(array $outputs, int $completedAt, int $durationMs): void
    {
        $this->update([
            'status' => GenerateTaskStatusConst::COMPLETED,
            'provider_outputs' => $outputs,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
            'updated_at' => $completedAt,
        ]);
    }

    /**
     * 任务关联的存储资源记录。
     */
    public function storages(): HasMany
    {
        return $this->hasMany(TaskStorage::class, 'task_record_id', 'id');
    }
}
