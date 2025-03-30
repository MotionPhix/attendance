<?php

use App\Services\SettingsService;

if (!function_exists('setting')) {
  /**
   * Get / set the specified setting value.
   *
   * If an array is passed as the key, we will assume you want to set an array of values.
   *
   * @param string|array<string, mixed>|null $key
   * @param mixed $default
   * @return mixed|\App\Services\SettingsService
   */
  function setting($key = null, $default = null)
  {
    $settings = app(SettingsService::class);

    if (is_null($key)) {
      return $settings;
    }

    if (is_array($key)) {
      $settings->set($key);

      return $settings;
    }

    return $settings->get($key, $default);
  }
}
