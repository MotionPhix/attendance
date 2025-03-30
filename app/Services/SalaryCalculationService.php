<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\SalaryRecord;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryCalculationService
{
  /**
   * Calculate salary for a specific employee for a given month.
   *
   * @param User $user
   * @param int $month
   * @param int $year
   * @return array<string, mixed>
   */
  public function calculateSalary(User $user, int $month, int $year): array
  {
    $employeeProfile = $user->employeeProfile;

    if (!$employeeProfile) {
      throw new \Exception('Employee profile not found.');
    }

    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    $baseAmount = $employeeProfile->base_salary;
    $deductions = 0;
    $bonuses = 0;
    $overtimePay = 0;

    // Get work schedule to determine standard hours
    $workSchedule = $this->getWorkSchedule($employeeProfile);

    // Calculate standard work hours per day
    $standardDailyHours = $this->calculateStandardDailyHours($workSchedule);

    // Get attendance logs for the month
    $attendanceLogs = AttendanceLog::where('user_id', $user->id)
      ->whereBetween('check_in_time', [$startDate, $endDate])
      ->get();

    // Calculate attendance-based deductions and overtime
    $attendanceCalculations = $this->calculateAttendanceDeductionsAndOvertime(
      $attendanceLogs,
      $employeeProfile,
      $standardDailyHours
    );

    $deductions += $attendanceCalculations['deductions'];
    $overtimePay += $attendanceCalculations['overtime_pay'];

    // Calculate leave deductions
    $leaveDeduction = $this->calculateLeaveDeductions($user->id, $month, $year, $baseAmount);
    $deductions += $leaveDeduction;

    // Calculate performance bonuses
    $performanceBonus = $this->calculatePerformanceBonus($user->id, $month, $year, $baseAmount);
    $bonuses += $performanceBonus;

    // Calculate attendance bonuses (perfect attendance, punctuality)
    $attendanceBonus = $this->calculateAttendanceBonus($user->id, $month, $year, $baseAmount);
    $bonuses += $attendanceBonus;

    // Calculate tax withholding (simplified example)
    $taxableIncome = $baseAmount + $overtimePay + $bonuses;
    $taxDeduction = $this->calculateTaxDeduction($taxableIncome);
    $deductions += $taxDeduction;

    // Calculate net amount
    $netAmount = $baseAmount - $deductions + $bonuses + $overtimePay;

    return [
      'user_id' => $user->id,
      'month' => $month,
      'year' => $year,
      'base_amount' => $baseAmount,
      'deductions' => [
        'total' => $deductions,
        'attendance' => $attendanceCalculations['deductions'],
        'leave' => $leaveDeduction,
        'tax' => $taxDeduction,
      ],
      'bonuses' => [
        'total' => $bonuses,
        'performance' => $performanceBonus,
        'attendance' => $attendanceBonus,
      ],
      'overtime_pay' => $overtimePay,
      'net_amount' => $netAmount,
      'details' => [
        'working_days' => $this->getWorkingDaysCount($startDate, $endDate),
        'present_days' => $attendanceLogs->count(),
        'absent_days' => $this->getWorkingDaysCount($startDate, $endDate) - $attendanceLogs->count(),
        'late_arrivals' => $attendanceLogs->where('late_minutes', '>', 0)->count(),
        'early_departures' => $attendanceLogs->where('early_departure_minutes', '>', 0)->count(),
        'overtime_hours' => round($overtimePay / ($employeeProfile->hourly_rate * 1.5), 2),
      ],
    ];
  }

  /**
   * Generate and store salary records for all employees for a given month.
   *
   * @param int $month
   * @param int $year
   * @return array<string, mixed>
   */
  public function generateMonthlySalaries(int $month, int $year): array
  {
    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    // Check if we're trying to generate salaries for a future month
    if ($startDate->isFuture()) {
      throw new \Exception('Cannot generate salaries for future months.');
    }

    // Check if the month has ended (if generating for current month)
    $now = Carbon::now();
    if ($startDate->month === $now->month && $startDate->year === $now->year && !$endDate->isPast()) {
      throw new \Exception('Cannot generate salaries before the month has ended.');
    }

    // Get all active employees
    $employees = User::whereHas('employeeProfile', function ($query) {
      $query->where('status', 'active');
    })->get();

    $results = [
      'total' => $employees->count(),
      'processed' => 0,
      'skipped' => 0,
      'errors' => 0,
      'details' => [],
    ];

    foreach ($employees as $employee) {
      try {
        // Check if salary record already exists
        $existingRecord = SalaryRecord::where('user_id', $employee->id)
          ->where('month', $month)
          ->where('year', $year)
          ->first();

        if ($existingRecord) {
          $results['skipped']++;
          $results['details'][] = [
            'user_id' => $employee->id,
            'name' => $employee->name,
            'status' => 'skipped',
            'message' => 'Salary record already exists.',
          ];
          continue;
        }

        // Calculate salary
        $salaryData = $this->calculateSalary($employee, $month, $year);

        // Create salary record
        SalaryRecord::create([
          'user_id' => $employee->id,
          'month' => $month,
          'year' => $year,
          'base_amount' => $salaryData['base_amount'],
          'deductions' => $salaryData['deductions']['total'],
          'bonuses' => $salaryData['bonuses']['total'],
          'overtime_pay' => $salaryData['overtime_pay'],
          'net_amount' => $salaryData['net_amount'],
          'details' => json_encode($salaryData),
          'status' => 'processed',
          'processed_at' => now(),
        ]);

        $results['processed']++;
        $results['details'][] = [
          'user_id' => $employee->id,
          'name' => $employee->name,
          'status' => 'processed',
          'net_amount' => $salaryData['net_amount'],
        ];
      } catch (\Exception $e) {
        Log::error('Salary calculation error: ' . $e->getMessage(), [
          'user_id' => $employee->id,
          'month' => $month,
          'year' => $year,
          'exception' => $e,
        ]);

        $results['errors']++;
        $results['details'][] = [
          'user_id' => $employee->id,
          'name' => $employee->name,
          'status' => 'error',
          'message' => $e->getMessage(),
        ];
      }
    }

    return $results;
  }

  /**
   * Get the work schedule for an employee.
   *
   * @param EmployeeProfile $employeeProfile
   * @return WorkSchedule|null
   */
  protected function getWorkSchedule(EmployeeProfile $employeeProfile): ?WorkSchedule
  {
    return WorkSchedule::where('department_id', $employeeProfile->department_id)
      ->where('is_default', true)
      ->first();
  }

  /**
   * Calculate standard daily work hours based on work schedule.
   *
   * @param WorkSchedule|null $workSchedule
   * @return float
   */
  protected function calculateStandardDailyHours(?WorkSchedule $workSchedule): float
  {
    if (!$workSchedule) {
      return 8.0; // Default to 8 hours if no schedule is set
    }

    $startTime = Carbon::parse($workSchedule->start_time);
    $endTime = Carbon::parse($workSchedule->end_time);
    $breakDuration = $workSchedule->break_duration / 60; // Convert minutes to hours

    return $endTime->diffInHours($startTime) - $breakDuration;
  }

  /**
   * Calculate attendance-based deductions and overtime pay.
   *
   * @param \Illuminate\Database\Eloquent\Collection $attendanceLogs
   * @param EmployeeProfile $employeeProfile
   * @param float $standardDailyHours
   * @return array<string, float>
   */
  protected function calculateAttendanceDeductionsAndOvertime($attendanceLogs, EmployeeProfile $employeeProfile, float $standardDailyHours): array
  {
    $deductions = 0;
    $overtimePay = 0;
    $perMinuteRate = $employeeProfile->getPerMinuteRate();

    foreach ($attendanceLogs as $log) {
      // Calculate late minutes deductions
      if ($log->late_minutes > 0) {
        $deductions += $log->late_minutes * $perMinuteRate;
      }

      // Calculate early departure deductions
      if ($log->early_departure_minutes > 0) {
        $deductions += $log->early_departure_minutes * $perMinuteRate;
      }

      // Calculate overtime if check-out time exists
      if ($log->check_out_time) {
        $checkInTime = Carbon::parse($log->check_in_time);
        $checkOutTime = Carbon::parse($log->check_out_time);
        $hoursWorked = $checkOutTime->diffInMinutes($checkInTime) / 60;

        // If worked more than standard hours, calculate overtime
        if ($hoursWorked > $standardDailyHours) {
          $overtimeHours = $hoursWorked - $standardDailyHours;
          // Assuming overtime is paid at 1.5x hourly rate
          $overtimePay += $overtimeHours * $employeeProfile->hourly_rate * 1.5;
        }
      }
    }

    return [
      'deductions' => $deductions,
      'overtime_pay' => $overtimePay,
    ];
  }

  /**
   * Calculate leave deductions for a given month.
   *
   * @param int $userId
   * @param int $month
   * @param int $year
   * @param float $baseSalary
   * @return float
   */
  protected function calculateLeaveDeductions(int $userId, int $month, int $year, float $baseSalary): float
  {
    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    // Get unpaid leaves for the month
    $unpaidLeaves = LeaveRequest::where('user_id', $userId)
      ->where('status', 'approved')
      ->where('leave_type', 'unpaid')
      ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('start_date', [$startDate, $endDate])
          ->orWhereBetween('end_date', [$startDate, $endDate]);
      })
      ->get();

    $totalDeduction = 0;
    $workingDaysInMonth = $this->getWorkingDaysCount($startDate, $endDate);
    $dailyRate = $baseSalary / $workingDaysInMonth;

    foreach ($unpaidLeaves as $leave) {
      // Calculate days that fall within the specified month
      $leaveStart = max(Carbon::parse($leave->start_date), $startDate);
      $leaveEnd = min(Carbon::parse($leave->end_date), $endDate);

      $leaveDays = $leaveStart->diffInDaysFiltered(function (Carbon $date) {
          return !$date->isWeekend(); // Skip weekends
        }, $leaveEnd) + 1; // +1 to include the end date

      // Calculate deduction based on daily rate
      $totalDeduction += $leaveDays * $dailyRate;
    }

    return $totalDeduction;
  }

  /**
   * Calculate performance bonus.
   *
   * @param int $userId
   * @param int $month
   * @param int $year
   * @param float $baseSalary
   * @return float
   */
  protected function calculatePerformanceBonus(int $userId, int $month, int $year, float $baseSalary): float
  {
    // This is a simplified implementation
    // In a real application, you would fetch performance reviews or metrics

    // For now, we'll just return 0 as a placeholder
    // In a real implementation, you might have a performance review system
    return 0;
  }

  /**
   * Calculate attendance bonus.
   *
   * @param int $userId
   * @param int $month
   * @param int $year
   * @param float $baseSalary
   * @return float
   */
  protected function calculateAttendanceBonus(int $userId, int $month, int $year, float $baseSalary): float
  {
    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    // Get attendance logs for the month
    $attendanceLogs = AttendanceLog::where('user_id', $userId)
      ->whereBetween('check_in_time', [$startDate, $endDate])
      ->get();

    $workingDays = $this->getWorkingDaysCount($startDate, $endDate);
    $presentDays = $attendanceLogs->count();
    $lateDays = $attendanceLogs->where('late_minutes', '>', 0)->count();
    $earlyDepartures = $attendanceLogs->where('early_departure_minutes', '>', 0)->count();

    $bonus = 0;

    // Perfect attendance bonus (present every working day with no lates or early departures)
    if ($presentDays === $workingDays && $lateDays === 0 && $earlyDepartures === 0) {
      $bonus += $baseSalary * 0.05; // 5% bonus for perfect attendance
    } // High attendance bonus (present at least 95% of working days)
    elseif ($presentDays >= $workingDays * 0.95) {
      $bonus += $baseSalary * 0.02; // 2% bonus for high attendance
    }

    // Punctuality bonus (no late arrivals)
    if ($lateDays === 0 && $presentDays > 0) {
      $bonus += $baseSalary * 0.01; // 1% bonus for perfect punctuality
    }

    return $bonus;
  }

  /**
   * Calculate tax deduction.
   *
   * @param float $grossIncome
   * @return float
   */
  protected function calculateTaxDeduction(float $grossIncome): float
  {
    // This is a simplified tax calculation
    // In a real application, this would be more complex based on tax brackets and regulations

    if ($grossIncome <= 1000) {
      return $grossIncome * 0.1; // 10% tax rate
    } elseif ($grossIncome <= 3000) {
      return 100 + ($grossIncome - 1000) * 0.15; // 15% on amount over 1000
    } else {
      return 100 + 300 + ($grossIncome - 3000) * 0.2; // 20% on amount over 3000
    }
  }

  /**
   * Get the number of working days in a date range.
   *
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return int
   */
  protected function getWorkingDaysCount(Carbon $startDate, Carbon $endDate): int
  {
    return $startDate->diffInDaysFiltered(function (Carbon $date) {
        return !$date->isWeekend(); // Skip weekends
      }, $endDate) + 1; // +1 to include the end date
  }

  /**
   * Get salary statistics for a department.
   *
   * @param int $departmentId
   * @param int $month
   * @param int $year
   * @return array<string, mixed>
   */
  public function getDepartmentSalaryStats(int $departmentId, int $month, int $year): array
  {
    $employees = User::whereHas('employeeProfile', function ($query) use ($departmentId) {
      $query->where('department_id', $departmentId);
    })->get();

    $salaryRecords = SalaryRecord::whereIn('user_id', $employees->pluck('id'))
      ->where('month', $month)
      ->where('year', $year)
      ->get();

    $totalBaseSalary = $salaryRecords->sum('base_amount');
    $totalDeductions = $salaryRecords->sum('deductions');
    $totalBonuses = $salaryRecords->sum('bonuses');
    $totalOvertimePay = $salaryRecords->sum('overtime_pay');
    $totalNetAmount = $salaryRecords->sum('net_amount');

    return [];
  }
}
