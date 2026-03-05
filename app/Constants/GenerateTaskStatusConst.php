<?php

namespace App\Constants;

/**
 * 生成任务状态常量。
 *
 * 约定：状态值与上游服务商回调状态保持一致，便于直接映射与排障。
 */
class GenerateTaskStatusConst
{

    /**
     * 已创建，等待进入调度流程。
     */
    public const string PENDING = 'PENDING';

    /**
     * 任务被拒绝（高风险生成任务等原因）。
     */
    public const string REJECTED = 'REJECTED';

    /**
     * 已进入队列，等待执行。
     */
    public const string IN_QUEUE = 'IN_QUEUE';

    /**
     * 执行中。
     */
    public const string IN_PROGRESS = 'IN_PROGRESS';

    /**
     * 已暂停（可恢复）。
     */
    public const string PAUSED = 'PAUSED';

    /**
     * 执行完成（成功）。
     */
    public const string COMPLETED = 'COMPLETED';

    /**
     * 执行失败。
     */
    public const string FAILED = 'FAILED';

    /**
     * 结果 URL 更新
     */
    public const string RESULT_URLS_UPDATE = 'RESULT_URLS_UPDATE';

}
