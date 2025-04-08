<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->hasAdminAccess();
  }

  public function rules(): array
  {
    return [
      'company_name' => 'required|string|max:255',
      'company_email' => 'required|email|max:255',
      'company_phone' => 'nullable|string|max:20',
      'company_address' => 'nullable|string|max:500',
      'company_logo' => 'nullable|image|max:2048',
      'timezone' => 'required|string|max:255',
      'date_format' => 'required|string|max:20',
      'time_format' => 'required|string|max:20',
    ];
  }
}
