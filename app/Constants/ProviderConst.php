<?php

namespace App\Constants;

/**
 * AI 推理服务商标识常量。
 *
 * 用于统一各模型 Handler、Job 及日志中对服务商的引用，
 * 避免硬编码字符串散落在各处。
 */
class ProviderConst
{
    /** WaveSpeed 服务商 */
    public const string WAVE_SPEED = 'wavespeed';

    /** Fal 服务商 */
    public const string FAL = 'fal';
}
