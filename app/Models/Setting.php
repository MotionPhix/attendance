<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
  use HasFactory;

  protected $fillable = [
    'key',
    'value',
    'group',
    'type',
    'description',
    'options',
    'is_public',
  ];

  protected $casts = [
    'options' => 'json',
    'is_public' => 'boolean',
  ];

  public static function getValueByKey(string $key, $default = null)
  {
    return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
      $setting = static::where('key', $key)->first();

      if (!$setting) {
        return $default;
      }

      return match ($setting->type) {
        'boolean' => (bool) $setting->value,
        'number' => (float) $setting->value,
        'json' => json_decode($setting->value, true),
        'array' => explode(',', $setting->value),
        default => $setting->value,
      };
    });
  }

  public static function setValueByKey(string $key, $value): void
  {
    $setting = static::where('key', $key)->first();

    if ($setting) {
      $setting->update(['value' => $value]);
      Cache::forget("setting.{$key}");
    }
  }

  protected static function boot()
  {
    parent::boot();

    static::saved(function ($setting) {
      Cache::forget("setting.{$setting->key}");
    });

    static::deleted(function ($setting) {
      Cache::forget("setting.{$setting->key}");
    });
  }
}
