<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
  /**
   * Get a setting value by key
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function get(string $key, mixed $default = null): mixed
  {
    return setting($key, $default);
  }

  public function updateGeneralSettings(array $data, ?UploadedFile $logo = null): void
  {
    foreach ($data as $key => $value) {
      if ($key !== 'company_logo') {
        setting([$key => $value]);
      }
    }

    if ($logo) {
      $path = $logo->store('public/logos');
      setting(['company_logo' => $path]);

      // Delete old logo if exists
      $oldLogo = setting('company_logo');
      if ($oldLogo && Storage::exists($oldLogo)) {
        Storage::delete($oldLogo);
      }
    }

    $this->saveAndClearCache();
  }

  public function updateAttendanceSettings(array $data): void
  {
    foreach ($data as $key => $value) {
      setting([$key => $value]);
    }

    $this->saveAndClearCache();
  }

  public function updateLeaveSettings(array $data): void
  {
    foreach ($data as $key => $value) {
      setting([$key => $value]);
    }

    $this->saveAndClearCache();
  }

  public function updateSalarySettings(array $data): void
  {
    foreach ($data as $key => $value) {
      setting([$key => $value]);
    }

    $this->saveAndClearCache();
  }

  private function saveAndClearCache(): void
  {
    setting()->save();
    Cache::tags(['settings'])->flush();
  }
}
