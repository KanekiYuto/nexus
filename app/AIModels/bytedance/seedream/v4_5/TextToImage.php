<?php

namespace App\AIModels\bytedance\seedream\v4_5;

use App\Constants\ProviderConst;
use App\Constants\StatusCode;
use Extensions\API\Fal;
use Extensions\API\WaveSpeed;
use Illuminate\Support\Facades\Validator;

/**
 * 字节 Seedream v4.5 文生图能力封装。
 *
 * 职责边界：
 * - 仅做模型层参数校验与服务商请求映射
 * - 不处理任务状态流转与回调发送（由 Job/Logic 层处理）
 */
class TextToImage
{
    /** 业务层统一模型标识（用于分发路由）。 */
    public const string MODEL_NAME = 'bytedance/seedream/v4.5/text-to-image';

    /** FAL 服务商侧模型标识。 */
    private const string FAL_MODEL = 'bytedance/seedream/v4.5/text-to-image';
    
    /** WaveSpeed 服务商侧模型标识。 */
    private const string WAVESPEED_MODEL = 'bytedance/seedream-v4.5';

    /**
     * 提交文生图任务，并按服务商转发请求。
     *
     * 约定：
     * - 入参校验失败时直接返回统一错误结构，不调用上游
     * - provider 为 fal 时转发到 FAL；其余值默认走 WaveSpeed
     *
     * @param string $provider 服务商标识（fal / wavespeed）
     * @param array $params 业务参数，至少包含 prompt 与 size
     * @param string $taskId 任务 ID
     *
     * @return array 响应结构：success/code/msg/provider_id/response
     */
    public static function submit(string $provider, array $params, string $taskId): array
    {
        $validationResult = self::validateParams($params);
        if ($validationResult !== null) {
            return $validationResult;
        }

        $normalizedParams = [
            'prompt' => (string)$params['prompt'],
            'size' => (string)$params['size'],
        ];

        return match ($provider) {
            ProviderConst::FAL => self::fal($normalizedParams, $taskId),
            default => self::waveSpeed($normalizedParams, $taskId),
        };
    }

    /**
     * 校验并规范化请求参数。
     *
     * size 格式约定为 "宽*高"，例如 "2048*2048"。
     *
     * @param array $params 输入参数
     * @return array|null 校验失败返回错误响应；成功返回 null
     */
    private static function validateParams(array $params): ?array
    {
        $validator = Validator::make($params, [
            'prompt' => ['required', 'string'],
            'size' => ['required', 'string', 'regex:/^\d+\*\d+$/'],
        ]);

        if (!$validator->passes()) {
            return [
                'success' => false,
                'code' => StatusCode::VALIDATION_ERROR,
                'msg' => collect($validator->errors()->toArray())->implode(','),
            ];
        }

        return null;
    }

    /**
     * 使用 FAL 服务商提交任务。
     *
     * FAL 的尺寸参数要求为 image_size: { width, height }，
     * 因此需要将 size（如 "1024*1024"）拆分为宽高整数。
     *
     * @param array $params 业务参数
     * @param string $taskId 任务 ID
     *
     * @return array 响应
     */
    private static function fal(array $params, string $taskId): array
    {
        [$width, $height] = self::parseSize($params['size']);

        return Fal::submit(self::FAL_MODEL, [
            'prompt' => $params['prompt'],
            'image_size' => [
                'width' => $width,
                'height' => $height,
            ]
        ], $taskId);
    }

    /**
     * 使用 WaveSpeed 服务商提交任务。
     *
     * WaveSpeed 直接接受 size 字符串，无需拆分尺寸。
     *
     * @param array $params 业务参数
     * @param string $taskId 任务 ID
     *
     * @return array 响应
     */
    private static function waveSpeed(array $params, string $taskId): array
    {
        return WaveSpeed::submit(self::WAVESPEED_MODEL, [
            'prompt' => $params['prompt'],
            'size' => $params['size'],
        ], $taskId);
    }

    /**
     * 将 "宽*高" 解析为整数尺寸。
     *
     * @param string $size 尺寸字符串
     * @return array{0: int, 1: int}
     */
    private static function parseSize(string $size): array
    {
        [$width, $height] = explode('*', $size, 2);
        return [(int)$width, (int)$height];
    }

}
