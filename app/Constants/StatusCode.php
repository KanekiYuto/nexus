<?php

namespace App\Constants;

class StatusCode
{

    /**
     * 成功响应
     *
     * @var int
     */
    public const int SUCCESS = 200;

    /**
     * 客户端请求错误（通用）
     * 用于参数校验失败等业务警告场景
     *
     * @var int
     */
    public const int WARN = 400;

    /**
     * 服务器内部错误
     * 用于未捕获异常等系统级错误
     *
     * @var int
     */
    public const int ERROR = 500;

    /**
     * 资源不存在
     * 适用于 RESTful API 中资源未找到场景
     *
     * @var int
     */
    public const int NOT_FOUND = 404;

    /**
     * 未认证
     * 需要登录但未提供有效凭证时返回
     *
     * @var int
     */
    public const int UNAUTHORIZED = 401;

    /**
     * 权限不足
     * 已认证但无权访问资源时返回
     *
     * @var int
     */
    public const int FORBIDDEN = 403;

    /**
     * 请求参数验证失败
     * 适用于表单验证错误（Laravel 422 标准）
     *
     * @var int
     */
    public const int VALIDATION_ERROR = 422;

    /**
     * 请求方法不允许
     * 如 POST 接口收到 GET 请求时返回
     *
     * @var int
     */
    public const int METHOD_NOT_ALLOWED = 405;

    /**
     * 服务不可用
     * 通常是维护模式时返回
     *
     * Service unavailable
     * Usually returns when in maintenance mode
     *
     * @link https://laravel.com/docs/12.x/configuration#maintenance-mode
     *
     * @var int
     */
    public const int SERVICE_UNAVAILABLE = 503;

}
