<?php

namespace App\Http\Controllers\v1;

use App\Constants\ProviderConst;
use App\Logic\v1\ModelLogic;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * 模型生成接口控制器。
 */
class ModelController
{

    /**
     * 创建生成任务。
     *
     * @param Request $request 请求参数
     * @return JsonResponse 统一 JSON 响应
     *
     * @throws ValidationException
     */
    public function generate(Request $request): JsonResponse
    {
        // app_id 由 AuthenticateAppToken 中间件从 token 注入，无需客户端传入
        $requestParams = $request->validate([
            'app_id' => ['required', 'ulid', 'exists:app,id'],
            'provider' => ['required', Rule::in([
                ProviderConst::WAVE_SPEED,
                ProviderConst::FAL,
            ])],
            'model' => ['required', Rule::in(
                array_keys(Config::get('model', []))
            )],
            'webhook_url' => ['required', 'url'],
            'custom_id' => ['required', 'string', 'max:128'],
            'parameters' => ['required', 'array'],
            'metadata' => ['nullable', 'array'],
            'fallback_provider' => ['nullable', Rule::in([
                ProviderConst::WAVE_SPEED,
                ProviderConst::FAL,
            ])],
            'delay_generation' => ['nullable', 'numeric', 'min:0', 'max:120'],
        ]);

        return ModelLogic::generate($requestParams);
    }

    /**
     * 接收服务商回调并标准化为内部完成事件。
     *
     * 说明：
     * - 仅处理已接入的服务商（wavespeed/fal），其他服务商直接返回成功占位响应
     * - 按服务商协议校验请求体，并在“任务完成”条件满足时提取 outputs
     * - 统一调用逻辑层 webhook() 完成落库与业务侧通知
     *
     * @param Request $request 服务商回调原始请求
     * @param string $provider 服务商标识（路由参数）
     * @param string $taskId 内部任务ID（路由参数）
     * @return JsonResponse 统一 JSON 响应
     */
    public function webhook(Request $request, string $provider, string $taskId): JsonResponse
    {
        // 非支持服务商：保留幂等返回，避免反复重试
        if (!in_array($provider, [ProviderConst::WAVE_SPEED, ProviderConst::FAL])) {
            return ApiResponse::success('None');
        }

        if ($provider === ProviderConst::FAL) {
            // FAL 回调格式：status + payload.images[*].url
            $requestParams = $request::validate([
                'status' => ['required', 'string'],
                'payload' => ['required', 'array'],
            ]);

            if ($requestParams['status'] !== 'OK') {
                return ApiResponse::success('None');
            }

            if (empty($requestParams['payload'])) {
                return ApiResponse::success('None');
            }

            if (empty($requestParams['payload']['images'])) {
                return ApiResponse::success('None');
            }

            ModelLogic::webhook($taskId, collect(
                $requestParams['payload']['images']
            )->map(fn($item) => $item['url'])->toArray());;
        } else {
            // WaveSpeed 回调格式：code + status + outputs
            $requestParams = $request->validate([
                'code' => ['required', 'numeric'],
                'status' => ['required', 'string'],
                'outputs' => ['required', 'array'],
            ]);

            if ($requestParams['code'] !== 0 || $requestParams['status'] !== 'completed') {
                return ApiResponse::success('None');
            }

            if (empty($requestParams['outputs'])) {
                return ApiResponse::success('None');
            }

            ModelLogic::webhook($taskId, $requestParams['outputs']);
        }

        return ApiResponse::success('Success');
    }

}
