<?php

if (!function_exists('setting')) {
  function setting($key = null, $default = null)
  {
    if (is_null($key)) {
      return app('settings');
    }

    if (is_array($key)) {
      foreach ($key as $k => $value) {
        \App\Models\Setting::setValueByKey($k, $value);
      }
      return;
    }

    return \App\Models\Setting::getValueByKey($key, $default);
  }
}
