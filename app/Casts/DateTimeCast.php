<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 时间戳（Y-m-d H:i:s）
 */
class DateTimeCast implements CastsAttributes
{

    /**
     * Cast the given value.
     *
     * @param Model                $model
     * @param string               $key
     * @param mixed                $value
     * @param array<string, mixed> $attributes
     *
     * @return string
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return Carbon::parse($value)->setTimezone(
            config('create.timezone')
        )->format('Y-m-d H:i:s');
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model                $model
     * @param string               $key
     * @param mixed                $value
     * @param array<string, mixed> $attributes
     *
     * @return int|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int|null
    {
        if (is_null($value)) {
            return null;
        }

        return is_numeric($value) ? $value : Carbon::parse($value)->timestamp;
    }

}
