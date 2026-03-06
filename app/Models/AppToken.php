<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
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

    public $incrementing = false;

    public $timestamps = false;
    protected $table = 'app_token';

    protected $keyType = 'string';

    protected $fillable = [
        'app_id',
        'value',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function isExpired(): bool
    {
        if (empty($this->expired_at)) {
            return false;
        }
        return $this->expired_at < time();
    }

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
