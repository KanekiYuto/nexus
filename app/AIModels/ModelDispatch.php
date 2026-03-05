<?php

namespace App\AIModels;

use App\AIModels\bytedance\seedream\v4_5\TextToImage;

/**
 * 模型路由分发器。
 */
class ModelDispatch
{
    /**
     * 模型与提交处理器映射。
     *
     * @var array<string, class-string>
     */
    private const array MODEL_HANDLERS = [
        TextToImage::MODEL_NAME => TextToImage::class,
    ];

    /**
     * 根据模型标识分发到对应实现。
     *
     * @param string $model 模型标识
     * @param string $provider 服务商标识
     * @param array $params 请求参数
     * @param string $taskId 任务 ID
     * @return array 响应
     */
    public static function submit(string $model, string $provider, array $params, string $taskId): array
    {
        $handler = self::MODEL_HANDLERS[$model] ?? null;

        if ($handler === null || !is_callable([$handler, 'submit'])) {
            return [
                'success' => false,
                'code' => 500,
                'msg' => 'Internal service error: Model does not exist',
            ];
        }

        return $handler::submit($provider, $params, $taskId);
    }

}
