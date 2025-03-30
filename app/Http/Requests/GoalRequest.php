<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalRequest extends FormRequest
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
      'title' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
      'target_date' => 'required|date',
      'status' => 'required|in:not_started,in_progress,completed',
      'progress' => 'required|integer|min:0|max:100',
    ];
  }

  /**
   * Prepare the data for validation.
   *
   * @return void
   */
  protected function prepareForValidation()
  {
    // If status is completed, ensure progress is 100%
    if ($this->status === 'completed' && $this->progress < 100) {
      $this->merge([
        'progress' => 100,
      ]);
    }

    // If status is not_started and progress > 0, adjust status
    if ($this->status === 'not_started' && $this->progress > 0) {
      $this->merge([
        'status' => 'in_progress',
      ]);
    }
  }
}
