<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalarySettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->hasAdminAccess();
  }

  public function rules(): array
  {
    return [
      'salary_payment_date' => 'required|integer|min:1|max:31',
      'overtime_rate' => 'required|numeric|min:1|max:5',
      'weekend_overtime_rate' => 'required|numeric|min:1|max:5',
      'holiday_overtime_rate' => 'required|numeric|min:1|max:5',
      'late_deduction_method' => [
        'required',
        Rule::in(['per_minute', 'fixed', 'hourly'])
      ],
      'late_deduction_amount' => 'required|numeric|min:0',
      'early_departure_deduction_method' => [
        'required',
        Rule::in(['per_minute', 'fixed', 'hourly'])
      ],
      'early_departure_deduction_amount' => 'required|numeric|min:0',
      'tax_calculation_method' => [
        'required',
        Rule::in(['progressive', 'flat'])
      ],
      'tax_brackets' => [
        'required_if:tax_calculation_method,progressive',
        'array',
        function ($attribute, $value, $fail) {
          if ($this->tax_calculation_method === 'progressive') {
            $this->validateTaxBrackets($value, $fail);
          }
        }
      ],
      'tax_brackets.*.from' => 'required|numeric|min:0',
      'tax_brackets.*.to' => 'nullable|numeric|min:0',
      'tax_brackets.*.rate' => 'required|numeric|min:0|max:100',
      'flat_tax_rate' => 'required_if:tax_calculation_method,flat|numeric|min:0|max:100',
      'enable_bonuses' => 'required|boolean',
      'enable_deductions' => 'required|boolean',
    ];
  }

  protected function prepareForValidation(): void
  {
    $this->merge([
      'enable_bonuses' => filter_var($this->enable_bonuses, FILTER_VALIDATE_BOOLEAN),
      'enable_deductions' => filter_var($this->enable_deductions, FILTER_VALIDATE_BOOLEAN),
    ]);
  }

  private function validateTaxBrackets(array $brackets, callable $fail): void
  {
    // Sort brackets by 'from' value
    usort($brackets, fn($a, $b) => $a['from'] <=> $b['from']);

    $previousTo = 0;
    foreach ($brackets as $index => $bracket) {
      // Check for gaps between brackets
      if ($bracket['from'] !== $previousTo) {
        $fail("Tax brackets must be continuous without gaps.");
        return;
      }

      // Check for overlapping brackets
      if ($index < count($brackets) - 1 &&
        $bracket['to'] !== null &&
        $bracket['to'] >= $brackets[$index + 1]['from']) {
        $fail("Tax brackets must not overlap.");
        return;
      }

      $previousTo = $bracket['to'] ?? PHP_FLOAT_MAX;
    }

    // Check if the last bracket extends to infinity (null)
    $lastBracket = end($brackets);
    if ($lastBracket['to'] !== null) {
      $fail("The last tax bracket must extend to infinity (to = null).");
    }
  }

  public function messages(): array
  {
    return [
      'tax_brackets.required_if' => 'Tax brackets are required when using progressive tax calculation.',
      'flat_tax_rate.required_if' => 'Flat tax rate is required when using flat tax calculation.',
      'salary_payment_date.between' => 'The salary payment date must be a valid day of the month.',
      'overtime_rate.min' => 'The overtime rate must be at least 1x the regular rate.',
      'tax_brackets.*.rate.max' => 'Tax rates cannot exceed 100%.',
    ];
  }
}
