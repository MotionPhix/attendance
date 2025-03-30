<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeProfileRequest extends FormRequest
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
      'user_id' => 'sometimes|required|exists:users,id',
      'department_id' => 'required|exists:departments,id',
      'position' => 'required|string|max:255',
      'hire_date' => 'required|date',
      'base_salary' => 'required|numeric|min:0',
      'hourly_rate' => 'nullable|numeric|min:0',
      'status' => 'required|in:active,on_leave,suspended,terminated',
    ];
  }
}
