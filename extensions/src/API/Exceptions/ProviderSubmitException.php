<?php

namespace Extensions\API\Exceptions;

use RuntimeException;
use Throwable;

/**
 * 服务商提交失败异常。
 *
 * 由 WaveSpeed / Fal 等服务商 submit() 在以下情况抛出：
 * - 网络连接失败
 * - 服务商返回非成功状态
 * - 服务商返回无效响应结构
 */
class ProviderSubmitException extends RuntimeException
{
    /**
     * @param string $message 可读错误描述
     * @param array $payload 服务商原始响应（用于落库和调试）
     * @param Throwable|null $previous 原始异常（如 ConnectionException）
     */
    public function __construct(
        string              $message,
        public readonly array $payload = [],
        ?Throwable          $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
