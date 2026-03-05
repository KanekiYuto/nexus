<?php

namespace App\Constants;

class GlobalConst
{

    /**
     * 鉴权守卫名称
     *
     * @var string
     */
    public const string AUTH_GUARD = 'global';

    /**
     * 鉴权中间件名称
     *
     * @var string
     */
    public const string AUTH_MIDDLEWARE = 'auth:' . self::AUTH_GUARD;

    /**
     * 文件存储名称
     *
     * @var string
     */
    public const string S3_R2_DISK = 's3';

}
