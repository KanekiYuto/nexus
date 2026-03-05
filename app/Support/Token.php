<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * 请求令牌工具类。
 *
 * 基于 Laravel Crypt（AES-256-CBC + APP_KEY）对载荷加密，
 * 令牌自包含过期时间，无需持久化存储即可完成校验。
 *
 * 注意：令牌在有效期内不可主动吊销，若需支持吊销请引入存储层（Redis 黑名单等）。
 */
class Token
{

    /**
     * 签发加密令牌。
     *
     * @param int $ttl 有效期（秒），默认 300 秒
     * @return string 加密后的令牌字符串
     */
    public static function issue(int $ttl = 300): string
    {
        $payload = json_encode([
            'iat' => time(),
            'exp' => time() + $ttl,
        ]);

        return Crypt::encryptString($payload);
    }

    /**
     * 校验令牌并返回载荷。
     *
     * @param string $token 待校验的令牌字符串
     * @return array{iat: int, exp: int} 解密后的载荷
     * @throws RuntimeException 令牌非法或已过期时抛出
     */
    public static function verify(string $token): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            throw new RuntimeException('Invalid token.');
        }

        if (!isset($payload['exp']) || time() > $payload['exp']) {
            throw new RuntimeException('Token has expired.');
        }

        return $payload;
    }

}
