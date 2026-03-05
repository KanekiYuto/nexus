<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $name
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property-read Collection<int, AppToken> $tokens
 */
class App extends Model
{
    protected $table = 'app';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status'     => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

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

    public function tokens(): HasMany
    {
        return $this->hasMany(AppToken::class, 'app_id');
    }

    public function isEnabled(): bool
    {
        return $this->status === 1;
    }
}
