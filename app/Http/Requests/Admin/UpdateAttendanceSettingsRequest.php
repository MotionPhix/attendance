<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->hasAdminAccess();
  }

  public function rules(): array
  {
    return [
      'check_in_tolerance_minutes' => 'required|integer|min:0|max:60',
      'auto_checkout_enabled' => 'required|boolean',
      'auto_checkout_time' => 'required|date_format:H:i',
      'weekend_days' => 'required|array',
      'weekend_days.*' => 'integer|min:0|max:6',
      'allow_ip_restriction' => 'required|boolean',
      'allowed_ip_addresses' => 'nullable|array',
      'allowed_ip_addresses.*' => 'ip',
      'allow_location_restriction' => 'required|boolean',
      'office_latitude' => [
        'nullable',
        'numeric',
        'required_if:allow_location_restriction,true',
        'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'
      ],
      'office_longitude' => [
        'nullable',
        'numeric',
        'required_if:allow_location_restriction,true',
        'regex:/^[-]?((([0-9]?[0-9]|1[0-7][0-9])\.(\d+))|(180(\.0+)?))$/'
      ],
      'location_radius_meters' => [
        'nullable',
        'integer',
        'required_if:allow_location_restriction,true',
        'min:10',
        'max:1000'
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'office_latitude.regex' => 'The latitude must be between -90 and 90 degrees.',
      'office_longitude.regex' => 'The longitude must be between -180 and 180 degrees.',
      'weekend_days.*.integer' => 'Weekend days must be valid days of the week (0-6).',
      'allowed_ip_addresses.*.ip' => 'Each IP address must be valid.',
    ];
  }
}
