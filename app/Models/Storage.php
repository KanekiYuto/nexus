<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 文件存储记录。
 *
 * 对应 S3 上的一个对象，记录其路径、访问 URL、
 * 文件名、MIME 类型和大小等元数据。
 *
 * @property string $id
 * @property string $key               S3 存储路径（key）
 * @property string $url               S3 访问 URL
 * @property string $filename          存储文件名（ulid + 后缀）
 * @property string $original_filename 原始文件名（来源 URL 路径）
 * @property string $type              资源类型（image / video / file）
 * @property string $mime_type         MIME 类型
 * @property int    $size              文件大小（字节）
 * @property int    $created_at
 * @property int    $updated_at
 */
class Storage extends Model
{
    /**
     * 存储表名。
     *
     * @var string
     */
    protected $table = 'storage';

    /**
     * 主键为 ULID，非自增。
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * ULID 使用字符串主键类型。
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 当前表使用的是整型时间戳字段，关闭 Eloquent 默认时间戳写入逻辑。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值字段。
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'key',
        'url',
        'filename',
        'original_filename',
        'type',
        'mime_type',
        'size',
        'created_at',
        'updated_at',
    ];

    /**
     * 字段类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size'       => 'integer',
            'created_at' => 'integer',
            'updated_at' => 'integer',
        ];
    }

    /**
     * 创建时自动填充 id、created_at、updated_at；
     * 更新时自动刷新 updated_at。
     */
    protected static function booted(): void
    {
        static::creating(function (Storage $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
            $now = time();
            $model->created_at ??= $now;
            $model->updated_at ??= $now;
        });

        static::updating(function (Storage $model) {
            $model->updated_at = time();
        });
    }

    /**
     * 引用该存储资源的任务结果记录。
     */
    public function taskResults(): HasMany
    {
        return $this->hasMany(TaskResult::class, 'storage_id', 'id');
    }
}
