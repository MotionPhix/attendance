<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
  /**
   * The settings cache key.
   *
   * @var string
   */
  protected $cacheKey = 'app_settings';

  /**
   * The settings cache expiration time in seconds.
   *
   * @var int
   */
  protected $cacheExpiration = 86400; // 24 hours

  /**
   * The settings collection.
   *
   * @var array<string, mixed>
   */
  protected $settings = [];

  /**
   * Create a new settings service instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->loadSettings();
  }

  /**
   * Load all settings from cache or database.
   *
   * @return void
   */
  protected function loadSettings(): void
  {
    $this->settings = Cache::remember($this->cacheKey, $this->cacheExpiration, function () {
      return Setting::all()->pluck('value', 'key')->toArray();
    });
  }

  /**
   * Get a setting value.
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function get(string $key, $default = null)
  {
    return $this->settings[$key] ?? $default;
  }

  /**
   * Set a setting value.
   *
   * @param string|array<string, mixed> $key
   * @param mixed $value
   * @return $this
   */
  public function set($key, $value = null)
  {
    if (is_array($key)) {
      foreach ($key as $k => $v) {
        $this->settings[$k] = $v;
      }
    } else {
      $this->settings[$key] = $value;
    }

    return $this;
  }

  /**
   * Check if a setting exists.
   *
   * @param string $key
   * @return bool
   */
  public function has(string $key): bool
  {
    return array_key_exists($key, $this->settings);
  }

  /**
   * Remove a setting.
   *
   * @param string $key
   * @return $this
   */
  public function forget(string $key)
  {
    unset($this->settings[$key]);

    return $this;
  }

  /**
   * Get all settings.
   *
   * @return array<string, mixed>
   */
  public function all(): array
  {
    return $this->settings;
  }

  /**
   * Save settings to the database.
   *
   * @return void
   */
  public function save(): void
  {
    foreach ($this->settings as $key => $value) {
      Setting::updateOrCreate(
        ['key' => $key],
        ['value' => $value]
      );
    }

    // Clear the cache
    Cache::forget($this->cacheKey);

    // Reload settings
    $this->loadSettings();
  }
}
