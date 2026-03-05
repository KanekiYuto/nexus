<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TaskStorage extends Model
{
    /**
     * 任务存储表名。
     *
     * @var string
     */
    protected $table = 'task_storage';

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
        'type',
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
            'created_at' => 'integer',
        ];
    }

    /**
     * 所属任务。
     */
    public function taskRecord(): BelongsTo
    {
        return $this->belongsTo(TaskRecord::class, 'task_record_id', 'id');
    }
}
