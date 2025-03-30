<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SettingController extends Controller
{
  /**
   * Display the system settings page.
   *
   * @return \Inertia\Response
   */
  public function index()
  {
    return Inertia::render('Admin/Settings/Index', [
      'settings' => [
        'company_name' => config('app.name'),
        'company_email' => config('mail.from.address'),
        'company_phone' => setting('company_phone', ''),
        'company_address' => setting('company_address', ''),
        'company_logo' => setting('company_logo', ''),
        'timezone' => config('app.timezone'),
        'date_format' => setting('date_format', 'Y-m-d'),
        'time_format' => setting('time_format', 'H:i'),
      ],
    ]);
  }

  /**
   * Update the system settings.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request)
  {
    $request->validate([
      'company_name' => 'required|string|max:255',
      'company_email' => 'required|email|max:255',
      'company_phone' => 'nullable|string|max:20',
      'company_address' => 'nullable|string|max:500',
      'company_logo' => 'nullable|image|max:2048',
      'timezone' => 'required|string|max:255',
      'date_format' => 'required|string|max:20',
      'time_format' => 'required|string|max:20',
    ]);

    // Update settings
    setting(['company_name' => $request->company_name]);
    setting(['company_email' => $request->company_email]);
    setting(['company_phone' => $request->company_phone]);
    setting(['company_address' => $request->company_address]);
    setting(['timezone' => $request->timezone]);
    setting(['date_format' => $request->date_format]);
    setting(['time_format' => $request->time_format]);

    // Handle logo upload
    if ($request->hasFile('company_logo')) {
      $path = $request->file('company_logo')->store('public/logos');
      setting(['company_logo' => $path]);
    }

    // Save settings
    setting()->save();

    // Clear cache
    Cache::flush();

    return redirect()->back()->with('success', 'System settings updated successfully.');
  }

  /**
   * Display the attendance settings page.
   *
   * @return \Inertia\Response
   */
  public function attendanceSettings()
  {
    return Inertia::render('Admin/Settings/Attendance', [
      'settings' => [
        'check_in_tolerance_minutes' => setting('check_in_tolerance_minutes', 5),
        'auto_checkout_enabled' => setting('auto_checkout_enabled', false),
        'auto_checkout_time' => setting('auto_checkout_time', '18:00'),
        'weekend_days' => setting('weekend_days', [0, 6]), // Sunday and Saturday
        'allow_ip_restriction' => setting('allow_ip_restriction', false),
        'allowed_ip_addresses' => setting('allowed_ip_addresses', []),
        'allow_location_restriction' => setting('allow_location_restriction', false),
        'office_latitude' => setting('office_latitude', ''),
        'office_longitude' => setting('office_longitude', ''),
        'location_radius_meters' => setting('location_radius_meters', 100),
      ],
    ]);
  }

  /**
   * Update the attendance settings.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateAttendanceSettings(Request $request)
  {
    $request->validate([
      'check_in_tolerance_minutes' => 'required|integer|min:0|max:60',
      'auto_checkout_enabled' => 'required|boolean',
      'auto_checkout_time' => 'required|string',
      'weekend_days' => 'required|array',
      'weekend_days.*' => 'integer|min:0|max:6',
      'allow_ip_restriction' => 'required|boolean',
      'allowed_ip_addresses' => 'nullable|array',
      'allowed_ip_addresses.*' => 'ip',
      'allow_location_restriction' => 'required|boolean',
      'office_latitude' => 'nullable|numeric',
      'office_longitude' => 'nullable|numeric',
      'location_radius_meters' => 'nullable|integer|min:10|max:1000',
    ]);

    // Update settings
    setting(['check_in_tolerance_minutes' => $request->check_in_tolerance_minutes]);
    setting(['auto_checkout_enabled' => $request->auto_checkout_enabled]);
    setting(['auto_checkout_time' => $request->auto_checkout_time]);
    setting(['weekend_days' => $request->weekend_days]);
    setting(['allow_ip_restriction' => $request->allow_ip_restriction]);
    setting(['allowed_ip_addresses' => $request->allowed_ip_addresses]);
    setting(['allow_location_restriction' => $request->allow_location_restriction]);
    setting(['office_latitude' => $request->office_latitude]);
    setting(['office_longitude' => $request->office_longitude]);
    setting(['location_radius_meters' => $request->location_radius_meters]);

    // Save settings
    setting()->save();

    // Clear cache
    Cache::flush();

    return redirect()->back()->with('success', 'Attendance settings updated successfully.');
  }

  /**
   * Display the leave settings page.
   *
   * @return \Inertia\Response
   */
  public function leaveSettings()
  {
    return Inertia::render('Admin/Settings/Leave', [
      'settings' => [
        'annual_leave_days' => setting('annual_leave_days', 20),
        'sick_leave_days' => setting('sick_leave_days', 10),
        'personal_leave_days' => setting('personal_leave_days', 5),
        'require_approval_for_leave' => setting('require_approval_for_leave', true),
        'min_days_before_leave_request' => setting('min_days_before_leave_request', 3),
        'allow_half_day_leave' => setting('allow_half_day_leave', true),
        'leave_accrual_method' => setting('leave_accrual_method', 'annual'), // annual, monthly, bi-monthly
        'leave_carryover_limit' => setting('leave_carryover_limit', 5),
        'leave_carryover_expiry_months' => setting('leave_carryover_expiry_months', 3),
      ],
    ]);
  }

  /**
   * Update the leave settings.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateLeaveSettings(Request $request)
  {
    $request->validate([
      'annual_leave_days' => 'required|integer|min:0|max:100',
      'sick_leave_days' => 'required|integer|min:0|max:100',
      'personal_leave_days' => 'required|integer|min:0|max:100',
      'require_approval_for_leave' => 'required|boolean',
      'min_days_before_leave_request' => 'required|integer|min:0|max:30',
      'allow_half_day_leave' => 'required|boolean',
      'leave_accrual_method' => 'required|string|in:annual,monthly,bi-monthly',
      'leave_carryover_limit' => 'required|integer|min:0|max:100',
      'leave_carryover_expiry_months' => 'required|integer|min:0|max:12',
    ]);

    // Update settings
    setting(['annual_leave_days' => $request->annual_leave_days]);
    setting(['sick_leave_days' => $request->sick_leave_days]);
    setting(['personal_leave_days' => $request->personal_leave_days]);
    setting(['require_approval_for_leave' => $request->require_approval_for_leave]);
    setting(['min_days_before_leave_request' => $request->min_days_before_leave_request]);
    setting(['allow_half_day_leave' => $request->allow_half_day_leave]);
    setting(['leave_accrual_method' => $request->leave_accrual_method]);
    setting(['leave_carryover_limit' => $request->leave_carryover_limit]);
    setting(['leave_carryover_expiry_months' => $request->leave_carryover_expiry_months]);

    // Save settings
    setting()->save();

    // Clear cache
    Cache::flush();

    return redirect()->back()->with('success', 'Leave settings updated successfully.');
  }

  /**
   * Display the salary settings page.
   *
   * @return \Inertia\Response
   */
  public function salarySettings()
  {
    return Inertia::render('Admin/Settings/Salary', [
      'settings' => [
        'salary_payment_date' => setting('salary_payment_date', 1), // Day of month
        'overtime_rate' => setting('overtime_rate', 1.5),
        'weekend_overtime_rate' => setting('weekend_overtime_rate', 2.0),
        'holiday_overtime_rate' => setting('holiday_overtime_rate', 2.5),
        'late_deduction_method' => setting('late_deduction_method', 'per_minute'), // per_minute, fixed, hourly
        'late_deduction_amount' => setting('late_deduction_amount', 0),
        'early_departure_deduction_method' => setting('early_departure_deduction_method', 'per_minute'),
        'early_departure_deduction_amount' => setting('early_departure_deduction_amount', 0),
        'tax_calculation_method' => setting('tax_calculation_method', 'progressive'), // progressive, flat
        'tax_brackets' => setting('tax_brackets', [
          ['from' => 0, 'to' => 1000, 'rate' => 10],
          ['from' => 1001, 'to' => 3000, 'rate' => 15],
          ['from' => 3001, 'to' => null, 'rate' => 20],
        ]),
        'flat_tax_rate' => setting('flat_tax_rate', 15),
        'enable_bonuses' => setting('enable_bonuses', true),
        'enable_deductions' => setting('enable_deductions', true),
      ],
    ]);
  }

  /**
   * Update the salary settings.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateSalarySettings(Request $request)
  {
    $request->validate([
      'salary_payment_date' => 'required|integer|min:1|max:31',
      'overtime_rate' => 'required|numeric|min:1|max:5',
      'weekend_overtime_rate' => 'required|numeric|min:1|max:5',
      'holiday_overtime_rate' => 'required|numeric|min:1|max:5',
      'late_deduction_method' => 'required|string|in:per_minute,fixed,hourly',
      'late_deduction_amount' => 'required|numeric|min:0',
      'early_departure_deduction_method' => 'required|string|in:per_minute,fixed,hourly',
      'early_departure_deduction_amount' => 'required|numeric|min:0',
      'tax_calculation_method' => 'required|string|in:progressive,flat',
      'tax_brackets' => 'required_if:tax_calculation_method,progressive|array',
      'tax_brackets.*.from' => 'required|numeric|min:0',
      'tax_brackets.*.to' => 'nullable|numeric|min:0',
      'tax_brackets.*.rate' => 'required|numeric|min:0|max:100',
      'flat_tax_rate' => 'required_if:tax_calculation_method,flat|numeric|min:0|max:100',
      'enable_bonuses' => 'required|boolean',
      'enable_deductions' => 'required|boolean',
    ]);

    // Update settings
    setting(['salary_payment_date' => $request->salary_payment_date]);
    setting(['overtime_rate' => $request->overtime_rate]);
    setting(['weekend_overtime_rate' => $request->weekend_overtime_rate]);
    setting(['holiday_overtime_rate' => $request->holiday_overtime_rate]);
    setting(['late_deduction_method' => $request->late_deduction_method]);
    setting(['late_deduction_amount' => $request->late_deduction_amount]);
    setting(['early_departure_deduction_method' => $request->early_departure_deduction_method]);
    setting(['early_departure_deduction_amount' => $request->early_departure_deduction_amount]);
    setting(['tax_calculation_method' => $request->tax_calculation_method]);
    setting(['tax_brackets' => $request->tax_brackets]);
    setting(['flat_tax_rate' => $request->flat_tax_rate]);
    setting(['enable_bonuses' => $request->enable_bonuses]);
    setting(['enable_deductions' => $request->enable_deductions]);

    // Save settings
    setting()->save();

    // Clear cache
    Cache::flush();

    return redirect()->back()->with('success', 'Salary settings updated successfully.');
  }
}
