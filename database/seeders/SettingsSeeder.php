<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $settings = [
      // Company settings
      'company_phone' => '',
      'company_address' => '',
      'company_logo' => '',
      'date_format' => 'Y-m-d',
      'time_format' => 'H:i',

      // Attendance settings
      'check_in_tolerance_minutes' => 5,
      'auto_checkout_enabled' => false,
      'auto_checkout_time' => '18:00',
      'weekend_days' => [0, 6], // Sunday and Saturday
      'allow_ip_restriction' => false,
      'allowed_ip_addresses' => [],
      'allow_location_restriction' => false,
      'office_latitude' => '',
      'office_longitude' => '',
      'location_radius_meters' => 100,

      // Leave settings
      'annual_leave_days' => 20,
      'sick_leave_days' => 10,
      'personal_leave_days' => 5,
      'require_approval_for_leave' => true,
      'min_days_before_leave_request' => 3,
      'allow_half_day_leave' => true,
      'leave_accrual_method' => 'annual', // annual, monthly, bi-monthly
      'leave_carryover_limit' => 5,
      'leave_carryover_expiry_months' => 3,

      // Salary settings
      'salary_payment_date' => 1, // Day of month
      'overtime_rate' => 1.5,
      'weekend_overtime_rate' => 2.0,
      'holiday_overtime_rate' => 2.5,
      'late_deduction_method' => 'per_minute', // per_minute, fixed, hourly
      'late_deduction_amount' => 0,
      'early_departure_deduction_method' => 'per_minute',
      'early_departure_deduction_amount' => 0,
      'tax_calculation_method' => 'progressive', // progressive, flat
      'tax_brackets' => [
        ['from' => 0, 'to' => 1000, 'rate' => 10],
        ['from' => 1001, 'to' => 3000, 'rate' => 15],
        ['from' => 3001, 'to' => null, 'rate' => 20],
      ],
      'flat_tax_rate' => 15,
      'enable_bonuses' => true,
      'enable_deductions' => true,
    ];

    foreach ($settings as $key => $value) {
      Setting::updateOrCreate(
        ['key' => $key],
        ['value' => $value]
      );
    }
  }
}
