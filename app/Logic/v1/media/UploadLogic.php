<?php

namespace App\Logic\v1\media;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 媒体上传业务逻辑。
 */
class UploadLogic
{

    /** 允许上传的图片后缀。 */
    private const array IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /** 允许上传的视频后缀。 */
    private const array VIDEO_EXTS = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv'];

    /** 各文件类型的大小上限（Byte）。 */
    private const array MAX_BYTES = [
        'image' => 10 * 1024 * 1024,
        'video' => 50 * 1024 * 1024,
    ];

    /**
     * 生成对象存储临时直传凭证（有效期 5 分钟）。
     *
     * size 须由客户端声明并会被写入 S3 预签名条件（ContentLength），
     * 使存储层在实际上传时强制校验字节数，无法通过凭证绕过大小限制。
     *
     * @param string $filename 原始文件名，用于提取后缀
     * @param int $size 文件字节数（Byte）
     * @return JsonResponse 统一 JSON 响应，receipt 含 url / headers / path
     * @throws ValidationException 文件类型不支持或大小超出上限时抛出
     */
    public static function temporary(string $filename, int $size): JsonResponse
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === '') {
            throw ValidationException::withMessages([
                'filename' => 'Filename must include a valid extension.',
            ]);
        }

        if (!in_array($extension, self::IMAGE_EXTS, true) && !in_array($extension, self::VIDEO_EXTS, true)) {
            throw ValidationException::withMessages([
                'filename' => "Unsupported file type '.{$extension}'. Only images and videos are allowed.",
            ]);
        }

        $maxBytes = self::resolveMaxBytes($extension);

        if ($size > $maxBytes) {
            $label = in_array($extension, self::IMAGE_EXTS, true) ? 'Image' : 'Video';
            throw ValidationException::withMessages([
                'size' => collect([
                    "$label file exceeds the size limit of ",
                    $maxBytes / 1024 / 1024,
                    " MB (got ",
                    round($size / 1024 / 1024, 2),
                    " MB).",
                ])->implode(''),
            ]);
        }

        $path = 'uploads/' . Str::ulid() . ($extension ? '.' . $extension : '');

        ['url' => $url, 'headers' => $headers] = Storage::temporaryUploadUrl(
            $path, now()->addMinutes(5), ['ContentLength' => $size]
        );

        return ApiResponse::receipt([
            'url' => $url,
            'headers' => $headers,
            'path' => $path,
        ]);
    }

    /**
     * 返回后缀对应的最大字节数。
     *
     * 仅在类型校验通过后调用，调用方保证后缀必定属于图片或视频。
     *
     * @param string $extension 文件后缀（小写，不含点）
     * @return int 最大字节数
     */
    private static function resolveMaxBytes(string $extension): int
    {
        if (in_array($extension, self::IMAGE_EXTS, true)) return self::MAX_BYTES['image'];
        return self::MAX_BYTES['video'];
    }

}
