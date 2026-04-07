<?php

namespace App\AIModels\Contracts;

/**
 * 提供模型尺寸字符串解析能力。
 *
 * 约定尺寸格式为 "宽*高"，例如 "1024*1024"。
 */
trait ParsesModelSize
{

    /**
     * 将 "宽*高" 解析为整数尺寸。
     *
     * @param string $size 尺寸字符串
     *
     * @return array{0: int, 1: int}
     */
    private static function parseSize(string $size): array
    {
        [$width, $height] = explode('*', $size, 2);

        return [(int)$width, (int)$height];
    }

}
