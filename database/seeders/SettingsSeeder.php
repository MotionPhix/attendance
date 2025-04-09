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
    /*$settings = [
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
    }*/

    $settings = [
      // General Settings
      [
        'key' => 'app_name',
        'value' => 'Attendance System',
        'group' => 'general',
        'type' => 'text',
        'description' => 'The name of your application',
        'is_public' => true,
      ],
      [
        'key' => 'company_name',
        'value' => 'Your Company',
        'group' => 'general',
        'type' => 'text',
        'description' => 'Your company name',
        'is_public' => true,
      ],

      // Regional Settings
      [
        'key' => 'timezone',
        'value' => 'UTC',
        'group' => 'regional',
        'type' => 'select',
        'description' => 'Default timezone for the application',
        'options' => array_combine(timezone_identifiers_list(), timezone_identifiers_list()),
        'is_public' => false,
      ],
      [
        'key' => 'date_format',
        'value' => 'Y-m-d',
        'group' => 'regional',
        'type' => 'select',
        'description' => 'Default date format',
        'options' => [
          'Y-m-d' => '2025-04-08',
          'm/d/Y' => '04/08/2025',
          'd/m/Y' => '08/04/2025',
          'M j, Y' => 'Apr 8, 2025',
        ],
        'is_public' => true,
      ],
      [
        'key' => 'time_format',
        'value' => 'H:i',
        'group' => 'regional',
        'type' => 'select',
        'description' => 'Default time format',
        'options' => [
          'H:i' => '14:30',
          'h:i A' => '02:30 PM',
        ],
        'is_public' => true,
      ],

      // Currency Settings
      [
        'key' => 'currency',
        'value' => 'USD',
        'group' => 'currency',
        'type' => 'select',
        'description' => 'Default currency for the application',
        'options' => [
          'USD' => 'US Dollar ($)',
          'EUR' => 'Euro (€)',
          'GBP' => 'British Pound (£)',
          // Add more currencies as needed
        ],
        'is_public' => true,
      ],
      [
        'key' => 'currency_position',
        'value' => 'before',
        'group' => 'currency',
        'type' => 'select',
        'description' => 'Position of the currency symbol',
        'options' => [
          'before' => 'Before amount ($100)',
          'after' => 'After amount (100$)',
        ],
        'is_public' => false,
      ],

      // Attendance Settings
      [
        'key' => 'work_start_time',
        'value' => '09:00',
        'group' => 'attendance',
        'type' => 'text',
        'description' => 'Default work start time',
        'is_public' => true,
      ],
      [
        'key' => 'work_end_time',
        'value' => '17:00',
        'group' => 'attendance',
        'type' => 'text',
        'description' => 'Default work end time',
        'is_public' => true,
      ],
      [
        'key' => 'late_threshold_minutes',
        'value' => '15',
        'group' => 'attendance',
        'type' => 'number',
        'description' => 'Minutes after work start time to mark as late',
        'is_public' => true,
      ],

      // Leave Settings
      [
        'key' => 'annual_leave_days',
        'value' => '20',
        'group' => 'leave',
        'type' => 'number',
        'description' => 'Default annual leave days',
        'is_public' => true,
      ],
      [
        'key' => 'sick_leave_days',
        'value' => '10',
        'group' => 'leave',
        'type' => 'number',
        'description' => 'Default sick leave days',
        'is_public' => true,
      ],

      // Email Settings
      [
        'key' => 'email_notifications',
        'value' => 'true',
        'group' => 'notifications',
        'type' => 'boolean',
        'description' => 'Enable email notifications',
        'is_public' => false,
      ],

      [
        'key' => 'notification_email',
        'value' => 'admin@example.com',
        'group' => 'notifications',
        'type' => 'text',
        'description' => 'Email address for notifications',
        'is_public' => false,
      ],

      [
        'key' => 'tax_brackets',
        'value' => json_encode([
          ['from' => 0, 'to' => 1000, 'rate' => 10],
          ['from' => 1001, 'to' => 3000, 'rate' => 15],
          ['from' => 3001, 'to' => null, 'rate' => 20],
        ]),
        'group' => 'salary',
        'type' => 'json',
        'description' => 'Progressive tax brackets',
        'is_public' => true,
      ],
    ];

    foreach ($settings as $setting) {
      Setting::updateOrCreate(['key' => $setting['key']], $setting);
    }
  }
}
