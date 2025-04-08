<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveSettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->hasAdminAccess();
  }

  public function rules(): array
  {
    return [
      'annual_leave_days' => 'required|integer|min:0|max:100',
      'sick_leave_days' => 'required|integer|min:0|max:100',
      'personal_leave_days' => 'required|integer|min:0|max:100',
      'require_approval_for_leave' => 'required|boolean',
      'min_days_before_leave_request' => 'required|integer|min:0|max:30',
      'allow_half_day_leave' => 'required|boolean',
      'leave_accrual_method' => [
        'required',
        Rule::in(['annual', 'monthly', 'bi-monthly'])
      ],
      'leave_carryover_limit' => 'required|integer|min:0|max:100',
      'leave_carryover_expiry_months' => 'required|integer|min:0|max:12',
    ];
  }

  protected function prepareForValidation(): void
  {
    // Convert string boolean values to actual booleans
    $this->merge([
      'require_approval_for_leave' => filter_var($this->require_approval_for_leave, FILTER_VALIDATE_BOOLEAN),
      'allow_half_day_leave' => filter_var($this->allow_half_day_leave, FILTER_VALIDATE_BOOLEAN),
    ]);
  }
}
