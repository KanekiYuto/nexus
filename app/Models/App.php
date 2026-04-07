<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 应用接入方模型。
 *
 * 记录第三方应用基础信息与启停状态。
 *
 * @property string $id
 * @property string $name
 * @property int    $status
 * @property int    $created_at
 * @property int    $updated_at
 * @property-read Collection<int, AppToken> $tokens
 */
class App extends Model
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
    protected $table = 'app';

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
        'name',
        'status',
    ];

    /**
     * 字段类型转换。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'     => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * 应用关联的令牌列表。
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(AppToken::class, 'app_id');
    }

    /**
     * 判断应用是否启用。
     */
    public function isEnabled(): bool
    {
        return $this->status === 1;
    }

    /**
     * 模型生命周期事件。
     *
     * 创建时补齐主键和时间字段，更新时刷新 `updated_at`。
     */
    protected static function booted(): void
    {
        static::creating(function (App $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
            $now = time();
            $model->created_at = $now;
            $model->updated_at = $now;
        });

        static::updating(function (App $model) {
            $model->updated_at = time();
        });
    }
}
