<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, mixed>
   */
  public function rules()
  {
    return [
      'start_date' => 'required|date|after_or_equal:today',
      'end_date' => 'required|date|after_or_equal:start_date',
      'leave_type' => 'required|in:annual,sick,personal,unpaid',
      'reason' => 'required|string|max:1000',
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function messages()
  {
    return [
      'start_date.after_or_equal' => 'Leave cannot be requested for past dates.',
      'end_date.after_or_equal' => 'End date must be on or after the start date.',
    ];
  }
}
