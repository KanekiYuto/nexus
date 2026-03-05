<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * 返回成功响应。
     *
     * @param string $msg 业务提示信息
     * @param array $data 附加响应数据（会并入顶层）
     */
    public static function success(string $msg = 'success', array $data = []): JsonResponse
    {
        return self::basic($msg, 200, $data);
    }

    /**
     * 返回错误响应。
     *
     * @param string $msg 错误提示信息
     * @param int $code 业务状态码（非 HTTP 状态码）
     * @param array $data 附加响应数据（会并入顶层）
     */
    public static function error(string $msg = 'error', int $code = 500, array $data = []): JsonResponse
    {
        return self::basic($msg, $code, $data);
    }

    /**
     * 返回基础 JSON 响应，统一输出 code/msg 结构。
     *
     * @param string $msg 业务提示信息
     * @param int $code 业务状态码（非 HTTP 状态码）
     * @param array $data 附加响应数据（会并入顶层）
     */
    public static function basic(string $msg = 'success', int $code = 200, array $data = []): JsonResponse
    {
        return response()->json(array_merge([
            'code' => $code,
            'msg' => $msg,
        ], $data));
    }

    /**
     * 返回包含 receipt 字段的响应。
     *
     * @param object|array $receipt 回执数据，会被规范为对象
     * @param string $msg 业务提示信息
     * @param int $code 业务状态码（非 HTTP 状态码）
     */
    public static function receipt(object|array $receipt, string $msg = 'success', int $code = 200): JsonResponse
    {
        return self::basic($msg, $code, [
            'receipt' => is_object($receipt) ? $receipt : (object) $receipt,
        ]);
    }

    /**
     * 返回包含 rows 字段的响应。
     *
     * @param array $rows 列表数据
     * @param string $msg 业务提示信息
     * @param int $code 业务状态码（非 HTTP 状态码）
     */
    public static function rows(array $rows, string $msg = 'success', int $code = 200): JsonResponse
    {
        return self::basic($msg, $code, [
            'rows' => $rows,
        ]);
    }
}
