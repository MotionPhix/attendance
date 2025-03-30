<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoodLogRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, mixed>
   */
  public function rules(): array
  {
    return [
      'mood_level' => 'required|integer|min:1|max:5',
      'notes' => 'nullable|string|max:1000',
      'need_support' => 'boolean',
    ];
  }

  /**
   * Prepare the data for validation.
   *
   * @return void
   */
  protected function prepareForValidation(): void
  {
    // If mood level is low (1-2) and need_support is not explicitly set,
    // ask if they need support
    if (in_array($this->mood_level, [1, 2]) && !$this->has('need_support')) {
      $this->merge([
        'need_support' => true,
      ]);
    }
  }
}
