<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 应用访问令牌模型。
 *
 * 用于鉴权调用方访问系统接口。
 *
 * @property string   $id
 * @property string   $app_id
 * @property string   $value
 * @property int|null $expired_at
 * @property int      $created_at
 * @property int      $updated_at
 * @property-read App $app
 */
class AppToken extends Model
{

    /**
     * 主键为 ULID，非自增。
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 当前表使用整型时间戳字段，关闭 Eloquent 默认时间戳行为。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 数据表名。
     *
     * @var string
     */
    protected $table = 'app_token';

    /**
     * 主键类型为字符串。
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 可批量赋值字段。
     *
     * @var list<string>
     */
    protected $fillable = [
        'app_id',
        'value',
        'expired_at',
    ];

    /**
     * 字段类型转换。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expired_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * 所属应用。
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    /**
     * 判断令牌是否已过期。
     */
    public function isExpired(): bool
    {
        if (empty($this->expired_at)) {
            return false;
        }
        return $this->expired_at < time();
    }

    /**
     * 模型生命周期事件。
     *
     * 创建时补齐主键和时间字段，更新时刷新 `updated_at`。
     */
    protected static function booted(): void
    {
        static::creating(function (AppToken $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
            $now = time();
            $model->created_at = $now;
            $model->updated_at = $now;
        });

        static::updating(function (AppToken $model) {
            $model->updated_at = time();
        });
    }
}
