<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceSettingsRequest;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdateLeaveSettingsRequest;
use App\Http\Requests\Admin\UpdateSalarySettingsRequest;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SettingController extends Controller
{
  public function __construct(
    private readonly SettingsService $settingsService
  ) {}

  public function index()
  {
    return Inertia::render('admin/settings/General', [
      'settings' => fn() => ([
        'company_name' => config('app.name'),
        'company_email' => config('mail.from.address'),
        'company_phone' => $this->settingsService->get('company_phone', ''),
        'company_address' => $this->settingsService->get('company_address', ''),
        'company_logo' => $this->settingsService->get('company_logo', ''),
        'date_format' => $this->settingsService->get('date_format', 'Y-m-d'),
        'time_format' => $this->settingsService->get('time_format', 'H:i'),
        'timezone' => config('app.timezone'),
      ])
    ]);
  }

  public function update(UpdateGeneralSettingsRequest $request)
  {
    $this->settingsService->updateGeneralSettings(
      $request->validated(),
      $request->file('company_logo')
    );

    return back()->with('success', 'System settings updated successfully.');
  }

  public function attendanceSettings()
  {
    return Inertia::render('admin/settings/Attendance', [
      'settings' => Cache::tags(['settings'])->remember('attendance_settings', now()->addHour(), fn() => [
        'check_in_tolerance_minutes' => setting('check_in_tolerance_minutes', 5),
        'auto_checkout_enabled' => setting('auto_checkout_enabled', false),
        'auto_checkout_time' => setting('auto_checkout_time', '18:00'),
        'weekend_days' => setting('weekend_days', [0, 6]),
        'allow_ip_restriction' => setting('allow_ip_restriction', false),
        'allowed_ip_addresses' => setting('allowed_ip_addresses', []),
        'allow_location_restriction' => setting('allow_location_restriction', false),
        'office_latitude' => setting('office_latitude', ''),
        'office_longitude' => setting('office_longitude', ''),
        'location_radius_meters' => setting('location_radius_meters', 100),
      ])
    ]);
  }

  public function updateAttendanceSettings(UpdateAttendanceSettingsRequest $request)
  {
    $this->settingsService->updateAttendanceSettings($request->validated());
    return back()->with('success', 'Attendance settings updated successfully.');
  }

  public function leaveSettings()
  {
    return Inertia::render('admin/settings/Leave', [
      'settings' => Cache::tags(['settings'])->remember('leave_settings', now()->addHour(), fn() => [
        'annual_leave_days' => setting('annual_leave_days', 20),
        'sick_leave_days' => setting('sick_leave_days', 10),
        'personal_leave_days' => setting('personal_leave_days', 5),
        'require_approval_for_leave' => setting('require_approval_for_leave', true),
        'min_days_before_leave_request' => setting('min_days_before_leave_request', 3),
        'allow_half_day_leave' => setting('allow_half_day_leave', true),
        'leave_accrual_method' => setting('leave_accrual_method', 'annual'),
        'leave_carryover_limit' => setting('leave_carryover_limit', 5),
        'leave_carryover_expiry_months' => setting('leave_carryover_expiry_months', 3),
      ])
    ]);
  }

  public function updateLeaveSettings(UpdateLeaveSettingsRequest $request)
  {
    $this->settingsService->updateLeaveSettings($request->validated());
    return back()->with('success', 'Leave settings updated successfully.');
  }

  public function salarySettings()
  {
    return Inertia::render('admin/settings/Salary', [
      'settings' => fn() => ([
        'salary_payment_date' => setting('salary_payment_date', 1),
        'overtime_rate' => setting('overtime_rate', 1.5),
        'weekend_overtime_rate' => setting('weekend_overtime_rate', 2.0),
        'holiday_overtime_rate' => setting('holiday_overtime_rate', 2.5),
        'late_deduction_method' => setting('late_deduction_method', 'per_minute'),
        'late_deduction_amount' => setting('late_deduction_amount', 0),
        'early_departure_deduction_method' => setting('early_departure_deduction_method', 'per_minute'),
        'early_departure_deduction_amount' => setting('early_departure_deduction_amount', 0),
        'tax_calculation_method' => setting('tax_calculation_method', 'progressive'),
        'tax_brackets' => setting('tax_brackets', [
          ['from' => 0, 'to' => 1000, 'rate' => 10],
          ['from' => 1001, 'to' => 3000, 'rate' => 15],
          ['from' => 3001, 'to' => null, 'rate' => 20],
        ]),
        'flat_tax_rate' => setting('flat_tax_rate', 15),
        'enable_bonuses' => setting('enable_bonuses', true),
        'enable_deductions' => setting('enable_deductions', true),
      ])
    ]);
  }

  public function updateSalarySettings(UpdateSalarySettingsRequest $request)
  {
    $this->settingsService->updateSalarySettings($request->validated());
    return back()->with('success', 'Salary settings updated successfully.');
  }
}
