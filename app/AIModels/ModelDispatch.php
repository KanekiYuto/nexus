<?php

namespace App\AIModels;

use App\AIModels\bytedance\seedream\v4_5\ImageEdit;
use App\AIModels\bytedance\seedream\v4_5\TextToImage;
use App\AIModels\Contracts\ModelHandlerContract;
use Extensions\API\Exceptions\ProviderSubmitException;
use Extensions\API\Fal;
use Extensions\API\WaveSpeed;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * 模型路由分发器。
 */
class ModelDispatch
{
    /**
     * 模型与处理器映射。
     *
     * @var array<string, class-string<ModelHandlerContract>>
     */
    public const array MODEL_HANDLERS = [
        TextToImage::MODEL_NAME => TextToImage::class,
        ImageEdit::MODEL_NAME => ImageEdit::class,
    ];

    /**
     * 获取所有模型标识。
     *
     * @return array<string> 所有模型标识
     */
    public static function getModels(): array
    {
        return collect(self::MODEL_HANDLERS)->keys()->toArray();
    }

    /**
     * 校验模型专属参数，在创建任务记录前调用。
     *
     * 模型不存在时抛出 ValidationException；
     * 参数非法时由 Handler 内部抛出 ValidationException。
     *
     * @param string $model 模型标识
     * @param array $params 业务参数
     * @throws ValidationException
     */
    public static function validate(string $model, array $params): void
    {
        $handler = self::MODEL_HANDLERS[$model] ?? null;

        if ($handler === null) {
            throw ValidationException::withMessages([
                'model' => 'Model does not exist',
            ]);
        }

        $handler::validateParams($params);
    }

    /**
     * 根据模型标识分发提交请求到对应处理器。
     *
     * @param string $model 模型标识
     * @param string $provider 服务商标识
     * @param array $params 业务参数（已通过 validate() 校验）
     * @param string $taskId 任务 ID
     * @return array 成功响应（provider_id / response）
     * @throws ProviderSubmitException 服务商提交失败时抛出
     * @throws RuntimeException 模型不存在时抛出
     */
    public static function submit(string $model, string $provider, array $params, string $taskId): array
    {
        $handler = self::MODEL_HANDLERS[$model] ?? null;

        if ($handler === null) {
            throw new RuntimeException('Internal service error: Model does not exist');
        }

        return $handler::submit($provider, $params, $taskId);
    }

}
