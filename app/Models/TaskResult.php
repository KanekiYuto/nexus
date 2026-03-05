<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 任务生成结果记录。
 *
 * 每条记录对应服务商输出的一个资源文件，
 * 转存完成后与 storage 表关联。
 *
 * @property string $id
 * @property string $task_record_id 所属任务 ID
 * @property string $storage_id     关联存储资源 ID
 * @property int    $order_index    在输出列表中的位置（0-based）
 * @property int    $created_at
 */
class TaskResult extends Model
{
    /**
     * 任务结果表名。
     *
     * @var string
     */
    protected $table = 'task_result';

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
        'task_record_id',
        'storage_id',
        'order_index',
        'created_at',
    ];

    /**
     * 字段类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'created_at'  => 'integer',
        ];
    }

    /**
     * 创建时自动填充 id 和 created_at。
     */
    protected static function booted(): void
    {
        static::creating(function (TaskResult $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
            $model->created_at ??= time();
        });
    }

    /**
     * 所属任务记录。
     */
    public function taskRecord(): BelongsTo
    {
        return $this->belongsTo(TaskRecord::class, 'task_record_id', 'id');
    }

    /**
     * 关联的存储资源。
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class, 'storage_id', 'id');
    }
}
