<?php

namespace App\AIModels\Contracts;

/**
 * 模型 Handler 契约。
 *
 * 所有模型实现类必须实现此接口，确保：
 * - validateParams 可在任务创建前被外部调用
 * - submit 接口签名统一，供 ModelDispatch 分发
 */
interface ModelHandlerContract
{
    /**
     * 校验模型专属入参。
     *
     * 校验失败时抛出 ValidationException，由全局异常处理器统一返回 422。
     *
     * @param array $params 业务参数
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateParams(array $params): void;

    /**
     * 向服务商提交生成任务。
     *
     * @param string $provider 服务商标识
     * @param array $params 业务参数（已通过 validateParams）
     * @param string $taskId 任务 ID
     * @return array 响应结构：success/code/msg/provider_id/response
     */
    public static function submit(string $provider, array $params, string $taskId): array;
}
