<?php

namespace App\Http\Controllers;

use App\Models\SalaryRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SalaryController extends Controller
{
  use AuthorizesRequests;

  /**
   * Display a listing of the user's salary records.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $user = Auth::user();

    // Get year from request or use current year
    $year = $request->input('year', now()->year);

    // Get salary records for the year
    $salaryRecords = SalaryRecord::where('user_id', $user->id)
      ->where('year', $year)
      ->orderBy('month', 'desc')
      ->get()
      ->map(function ($record) {
        return [
          'id' => $record->id,
          'month' => $record->month,
          'year' => $record->year,
          'period' => $record->getPeriodName(),
          'base_amount' => $record->base_amount,
          'deductions' => $record->deductions,
          'bonuses' => $record->bonuses,
          'overtime_pay' => $record->overtime_pay,
          'net_amount' => $record->net_amount,
          'status' => $record->status,
          'processed_at' => $record->processed_at ? $record->processed_at->format('F d, Y') : null,
          'paid_at' => $record->paid_at ? $record->paid_at->format('F d, Y') : null,
        ];
      });

    // Get available years
    $availableYears = SalaryRecord::where('user_id', $user->id)
      ->select('year')
      ->distinct()
      ->orderBy('year', 'desc')
      ->pluck('year')
      ->toArray();

    // If no records yet, add current year
    if (empty($availableYears)) {
      $availableYears[] = now()->year;
    }

    // Calculate yearly totals
    $yearlyTotals = [
      'base_amount' => $salaryRecords->sum('base_amount'),
      'deductions' => $salaryRecords->sum('deductions'),
      'bonuses' => $salaryRecords->sum('bonuses'),
      'overtime_pay' => $salaryRecords->sum('overtime_pay'),
      'net_amount' => $salaryRecords->sum('net_amount'),
    ];

    // Get latest salary record
    $latestSalary = $salaryRecords->first();

    return Inertia::render('Salary/Index', [
      'salaryRecords' => $salaryRecords,
      'yearlyTotals' => $yearlyTotals,
      'latestSalary' => $latestSalary,
      'availableYears' => $availableYears,
      'selectedYear' => (int) $year,
      'statuses' => [
        'pending' => 'Pending',
        'processed' => 'Processed',
        'paid' => 'Paid',
      ],
    ]);
  }

  /**
   * Display the specified salary record as a payslip.
   *
   * @param SalaryRecord $salary
   * @return \Inertia\Response
   */
  public function payslip(SalaryRecord $salary)
  {
    $this->authorize('view', $salary);

    // Get employee profile
    $user = Auth::user();
    $employeeProfile = $user->employeeProfile;

    // Get attendance data for the month
    $startDate = Carbon::createFromDate($salary->year, $salary->month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($salary->year, $salary->month, 1)->endOfMonth();

    $attendanceStats = \App\Models\AttendanceLog::getSummary($user->id, $startDate, $endDate);

    return Inertia::render('Salary/Payslip', [
      'salary' => [
        'id' => $salary->id,
        'month' => $salary->month,
        'year' => $salary->year,
        'period' => $salary->getPeriodName(),
        'base_amount' => $salary->base_amount,
        'deductions' => $salary->deductions,
        'bonuses' => $salary->bonuses,
        'overtime_pay' => $salary->overtime_pay,
        'net_amount' => $salary->net_amount,
        'status' => $salary->status,
        'processed_at' => $salary->processed_at ? $salary->processed_at->format('F d, Y') : null,
        'paid_at' => $salary->paid_at ? $salary->paid_at->format('F d, Y') : null,
      ],
      'employee' => [
        'name' => $user->name,
        'email' => $user->email,
        'position' => $employeeProfile ? $employeeProfile->position : null,
        'department' => $employeeProfile && $employeeProfile->department ? $employeeProfile->department->name : null,
        'hire_date' => $employeeProfile ? $employeeProfile->hire_date->format('F d, Y') : null,
      ],
      'attendanceStats' => $attendanceStats,
      'generatedAt' => now()->format('F d, Y h:i A'),
    ]);
  }

  /**
   * Download the specified salary record as a PDF payslip.
   *
   * @param SalaryRecord $salary
   * @return \Illuminate\Http\Response
   */
  public function downloadPayslip(SalaryRecord $salary)
  {
    $this->authorize('view', $salary);

    $user = Auth::user();
    $employeeProfile = $user->employeeProfile;

    // Get attendance data for the month
    $startDate = Carbon::createFromDate($salary->year, $salary->month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($salary->year, $salary->month, 1)->endOfMonth();

    $attendanceStats = \App\Models\AttendanceLog::getSummary($user->id, $startDate, $endDate);

    $pdf = \PDF::loadView('payslips.employee', [
      'salary' => $salary,
      'employee' => $user,
      'employeeProfile' => $employeeProfile,
      'department' => $employeeProfile ? $employeeProfile->department : null,
      'attendanceStats' => $attendanceStats,
      'period' => $salary->getPeriodName(),
      'generatedAt' => now()->format('F d, Y h:i A'),
    ]);

    return $pdf->download("payslip-{$user->name}-{$salary->year}-{$salary->month}.pdf");
  }
}
