<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getMany(array $keysWithDefaults): array
    {
        $rows = static::query()->whereIn('key', array_keys($keysWithDefaults))->pluck('value', 'key');

        $result = [];
        foreach ($keysWithDefaults as $key => $default) {
            $result[$key] = $rows[$key] ?? $default;
        }
        return $result;
    }
}