<?php

namespace App\Http\Controllers\v1\media;

use App\Logic\v1\media\UploadLogic;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class UploadController
{

    /**
     * 申请对象存储临时直传凭证。
     *
     * @param Request $request 请求参数
     * @return JsonResponse 统一 JSON 响应
     * @throws ValidationException
     */
    public function temporary(Request $request): JsonResponse
    {
        ['filename' => $filename, 'size' => $size] = $request::validate([
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ]);

        return UploadLogic::temporary($filename, $size);
    }

}
