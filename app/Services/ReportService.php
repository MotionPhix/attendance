<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\SalaryRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
  /**
   * Generate daily attendance report.
   *
   * @param string|null $date
   * @param int|null $departmentId
   * @return array<string, mixed>
   */
  public function generateDailyAttendanceReport(?string $date = null, ?int $departmentId = null): array
  {
    $date = $date ? Carbon::parse($date) : Carbon::today();

    $query = AttendanceLog::with(['user.employeeProfile.department'])
      ->whereDate('check_in_time', $date);

    if ($departmentId) {
      $query->whereHas('user.employeeProfile', function ($q) use ($departmentId) {
        $q->where('department_id', $departmentId);
      });
    }

    $logs = $query->get();

    // Get all active employees
    $employeeQuery = EmployeeProfile::where('status', 'active');
    if ($departmentId) {
      $employeeQuery->where('department_id', $departmentId);
    }
    $totalActiveEmployees = $employeeQuery->count();

    // Calculate statistics
    $presentCount = $logs->count();
    $lateCount = $logs->where('late_minutes', '>', 0)->count();
    $earlyDepartureCount = $logs->where('early_departure_minutes', '>', 0)->count();
    $absentCount = $totalActiveEmployees - $presentCount;

    // Calculate average late minutes and early departure minutes
    $avgLateMinutes = $logs->where('late_minutes', '>', 0)->avg('late_minutes') ?? 0;
    $avgEarlyDepartureMinutes = $logs->where('early_departure_minutes', '>', 0)->avg('early_departure_minutes') ?? 0;

    // Group by department
    $departmentStats = [];
    if (!$departmentId) {
      $departmentStats = $logs->groupBy(function ($log) {
        return $log->user->employeeProfile->department->name ?? 'Unknown';
      })->map(function ($deptLogs) {
        return [
          'count' => $deptLogs->count(),
          'late_count' => $deptLogs->where('late_minutes', '>', 0)->count(),
          'early_departure_count' => $deptLogs->where('early_departure_minutes', '>', 0)->count(),
        ];
      })->toArray();
    }

    return [
      'date' => $date->format('Y-m-d'),
      'formatted_date' => $date->format('F d, Y'),
      'department_id' => $departmentId,
      'department_name' => $departmentId ? Department::find($departmentId)->name : 'All Departments',
      'total_active_employees' => $totalActiveEmployees,
      'present_count' => $presentCount,
      'absent_count' => $absentCount,
      'late_count' => $lateCount,
      'early_departure_count' => $earlyDepartureCount,
      'attendance_rate' => $totalActiveEmployees > 0 ? round(($presentCount / $totalActiveEmployees) * 100, 1) : 0,
      'punctuality_rate' => $presentCount > 0 ? round((($presentCount - $lateCount) / $presentCount) * 100, 1) : 0,
      'avg_late_minutes' => round($avgLateMinutes, 1),
      'avg_early_departure_minutes' => round($avgEarlyDepartureMinutes, 1),
      'department_stats' => $departmentStats,
      'logs' => $logs->map(function ($log) {
        return [
          'id' => $log->id,
          'user_id' => $log->user_id,
          'employee_name' => $log->user->name,
          'department' => $log->user->employeeProfile->department->name ?? 'Unknown',
          'position' => $log->user->employeeProfile->position ?? 'Unknown',
          'check_in_time' => $log->check_in_time->format('H:i:s'),
          'check_out_time' => $log->check_out_time ? $log->check_out_time->format('H:i:s') : null,
          'late_minutes' => $log->late_minutes,
          'early_departure_minutes' => $log->early_departure_minutes,
          'status' => $log->status,
          'notes' => $log->notes,
        ];
      }),
    ];
  }

  /**
   * Generate monthly attendance report.
   *
   * @param int|null $month
   * @param int|null $year
   * @param int|null $departmentId
   * @return array<string, mixed>
   */
  public function generateMonthlyAttendanceReport(?int $month = null, ?int $year = null, ?int $departmentId = null): array
  {
    $month = $month ?? Carbon::now()->month;
    $year = $year ?? Carbon::now()->year;

    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    // Get all active employees
    $employeeQuery = EmployeeProfile::where('status', 'active');
    if ($departmentId) {
      $employeeQuery->where('department_id', $departmentId);
    }
    $employees = $employeeQuery->with('user')->get();

    $totalWorkingDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
        return !$date->isWeekend();
      }, $endDate) + 1;

    // Get attendance logs for the month
    $query = AttendanceLog::whereBetween('check_in_time', [$startDate, $endDate]);
    if ($departmentId) {
      $query->whereHas('user.employeeProfile', function ($q) use ($departmentId) {
        $q->where('department_id', $departmentId);
      });
    }
    $logs = $query->get();

    // Calculate daily stats
    $dailyStats = [];
    $currentDate = $startDate->copy();
    while ($currentDate->lte($endDate)) {
      $dayLogs = $logs->filter(function ($log) use ($currentDate) {
        return $log->check_in_time->isSameDay($currentDate);
      });

      $dailyStats[] = [
        'date' => $currentDate->format('Y-m-d'),
        'day' => $currentDate->format('d'),
        'day_name' => $currentDate->format('D'),
        'is_weekend' => $currentDate->isWeekend(),
        'present_count' => $dayLogs->count(),
        'absent_count' => $employees->count() - $dayLogs->count(),
        'late_count' => $dayLogs->where('late_minutes', '>', 0)->count(),
        'early_departure_count' => $dayLogs->where('early_departure_minutes', '>', 0)->count(),
        'attendance_rate' => $employees->count() > 0 ? round(($dayLogs->count() / $employees->count()) * 100, 1) : 0,
      ];

      $currentDate->addDay();
    }

    // Calculate employee stats
    $employeeStats = [];
    foreach ($employees as $employee) {
      $employeeLogs = $logs->where('user_id', $employee->user_id);
      $presentDays = $employeeLogs->count();
      $lateDays = $employeeLogs->where('late_minutes', '>', 0)->count();
      $earlyDepartureDays = $employeeLogs->where('early_departure_minutes', '>', 0)->count();

      $employeeStats[] = [
        'user_id' => $employee->user_id,
        'employee_name' => $employee->user->name,
        'department' => $employee->department->name ?? 'Unknown',
        'position' => $employee->position,
        'present_days' => $presentDays,
        'absent_days' => $totalWorkingDays - $presentDays,
        'late_days' => $lateDays,
        'early_departure_days' => $earlyDepartureDays,
        'attendance_rate' => $totalWorkingDays > 0 ? round(($presentDays / $totalWorkingDays) * 100, 1) : 0,
        'punctuality_rate' => $presentDays > 0 ? round((($presentDays - $lateDays) / $presentDays) * 100, 1) : 0,
      ];
    }

    // Calculate department stats
    $departmentStats = [];
    if (!$departmentId) {
      $departmentStats = $logs->groupBy(function ($log) {
        return $log->user->employeeProfile->department->name ?? 'Unknown';
      })->map(function ($deptLogs) use ($employees, $totalWorkingDays) {
        $deptEmployeeIds = $deptLogs->pluck('user_id')->unique();
        $deptEmployeeCount = $employees->whereIn('user_id', $deptEmployeeIds)->count();
        $totalPossibleAttendance = $deptEmployeeCount * $totalWorkingDays;
        $presentCount = $deptLogs->count();

        return [
          'employee_count' => $deptEmployeeCount,
          'present_count' => $presentCount,
          'late_count' => $deptLogs->where('late_minutes', '>', 0)->count(),
          'early_departure_count' => $deptLogs->where('early_departure_minutes', '>', 0)->count(),
          'attendance_rate' => $totalPossibleAttendance > 0 ? round(($presentCount / $totalPossibleAttendance) * 100, 1) : 0,
        ];
      })->toArray();
    }

    // Calculate summary statistics
    $totalPresentDays = $logs->count();
    $totalPossibleAttendance = $employees->count() * $totalWorkingDays;
    $totalLateDays = $logs->where('late_minutes', '>', 0)->count();
    $totalEarlyDepartureDays = $logs->where('early_departure_minutes', '>', 0)->count();

    return [
      'month' => $month,
      'year' => $year,
      'formatted_period' => $startDate->format('F Y'),
      'department_id' => $departmentId,
      'department_name' => $departmentId ? Department::find($departmentId)->name : 'All Departments',
      'total_employees' => $employees->count(),
      'total_working_days' => $totalWorkingDays,
      'total_present_days' => $totalPresentDays,
      'total_absent_days' => $totalPossibleAttendance - $totalPresentDays,
      'total_late_days' => $totalLateDays,
      'total_early_departure_days' => $totalEarlyDepartureDays,
      'attendance_rate' => $totalPossibleAttendance > 0 ? round(($totalPresentDays / $totalPossibleAttendance) * 100, 1) : 0,
      'punctuality_rate' => $totalPresentDays > 0 ? round((($totalPresentDays - $totalLateDays) / $totalPresentDays) * 100, 1) : 0,
      'daily_stats' => $dailyStats,
      'employee_stats' => $employeeStats,
      'department_stats' => $departmentStats,
    ];
  }

  /**
   * Generate department attendance report.
   *
   * @param int|null $month
   * @param int|null $year
   * @return array<string, mixed>
   */
  public function generateDepartmentAttendanceReport(?int $month = null, ?int $year = null): array
  {
    $month = $month ?? Carbon::now()->month;
    $year = $year ?? Carbon::now()->year;

    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    $departments = Department::all();
    $departmentReports = [];

    foreach ($departments as $department) {
      $departmentReports[$department->id] = $this->generateMonthlyAttendanceReport($month, $year, $department->id);
    }

    // Calculate comparison metrics
    $comparisonData = [];
    foreach ($departmentReports as $departmentId => $report) {
      $comparisonData[] = [
        'department_id' => $departmentId,
        'department_name' => $report['department_name'],
        'employee_count' => $report['total_employees'],
        'attendance_rate' => $report['attendance_rate'],
        'punctuality_rate' => $report['punctuality_rate'],
        'late_days_percentage' => $report['total_present_days'] > 0 ? round(($report['total_late_days'] / $report['total_present_days']) * 100, 1) : 0,
        'early_departure_percentage' => $report['total_present_days'] > 0 ? round(($report['total_early_departure_days'] / $report['total_present_days']) * 100, 1) : 0,
      ];
    }

    return [
      'month' => $month,
      'year' => $year,
      'formatted_period' => $startDate->format('F Y'),
      'department_reports' => $departmentReports,
      'comparison_data' => $comparisonData,
    ];
  }

  /**
   * Generate monthly salary report.
   *
   * @param int|null $month
   * @param int|null $year
   * @param int|null $departmentId
   * @return array<string, mixed>
   */
  public function generateMonthlySalaryReport(?int $month = null, ?int $year = null, ?int $departmentId = null): array
  {
    $month = $month ?? Carbon::now()->month;
    $year = $year ?? Carbon::now()->year;

    $query = SalaryRecord::where('month', $month)
      ->where('year', $year)
      ->with(['user.employeeProfile.department']);

    if ($departmentId) {
      $query->whereHas('user.employeeProfile', function ($q) use ($departmentId) {
        $q->where('department_id', $departmentId);
      });
    }

    $salaryRecords = $query->get();

    // Calculate summary statistics
    $totalBaseAmount = $salaryRecords->sum('base_amount');
    $totalDeductions = $salaryRecords->sum('deductions');
    $totalBonuses = $salaryRecords->sum('bonuses');
    $totalOvertimePay = $salaryRecords->sum('overtime_pay');
    $totalNetAmount = $salaryRecords->sum('net_amount');

    // Group by status
    $statusCounts = $salaryRecords->groupBy('status')
      ->map(function ($records) {
        return $records->count();
      })
      ->toArray();

    // Group by department
    $departmentStats = [];
    if (!$departmentId) {
      $departmentStats = $salaryRecords->groupBy(function ($record) {
        return $record->user->employeeProfile->department->name ?? 'Unknown';
      })->map(function ($deptRecords) {
        return [
          'count' => $deptRecords->count(),
          'total_base_amount' => $deptRecords->sum('base_amount'),
          'total_deductions' => $deptRecords->sum('deductions'),
          'total_bonuses' => $deptRecords->sum('bonuses'),
          'total_overtime_pay' => $deptRecords->sum('overtime_pay'),
          'total_net_amount' => $deptRecords->sum('net_amount'),
          'avg_net_amount' => $deptRecords->avg('net_amount'),
        ];
      })->toArray();
    }

    return [
      'month' => $month,
      'year' => $year,
      'formatted_period' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
      'department_id' => $departmentId,
      'department_name' => $departmentId ? Department::find($departmentId)->name : 'All Departments',
      'total_records' => $salaryRecords->count(),
      'total_base_amount' => $totalBaseAmount,
      'total_deductions' => $totalDeductions,
      'total_bonuses' => $totalBonuses,
      'total_overtime_pay' => $totalOvertimePay,
      'total_net_amount' => $totalNetAmount,
      'avg_net_amount' => $salaryRecords->count() > 0 ? $totalNetAmount / $salaryRecords->count() : 0,
      'status_counts' => $statusCounts,
      'department_stats' => $departmentStats,
      'records' => $salaryRecords->map(function ($record) {
        return [
          'id' => $record->id,
          'user_id' => $record->user_id,
          'employee_name' => $record->user->name,
          'department' => $record->user->employeeProfile->department->name ?? 'Unknown',
          'position' => $record->user->employeeProfile->position ?? 'Unknown',
          'base_amount' => $record->base_amount,
          'deductions' => $record->deductions,
          'bonuses' => $record->bonuses,
          'overtime_pay' => $record->overtime_pay,
          'net_amount' => $record->net_amount,
          'status' => $record->status,
          'processed_at' => $record->processed_at ? $record->processed_at->format('Y-m-d H:i:s') : null,
          'paid_at' => $record->paid_at ? $record->paid_at->format('Y-m-d H:i:s') : null,
        ];
      }),
    ];
  }

  /**
   * Generate department salary report.
   *
   * @param int|null $month
   * @param int|null $year
   * @return array<string, mixed>
   */
  public function generateDepartmentSalaryReport(?int $month = null, ?int $year = null): array
  {
    $month = $month ?? Carbon::now()->month;
    $year = $year ?? Carbon::now()->year;

    $departments = Department::all();
    $departmentReports = [];

    foreach ($departments as $department) {
      $departmentReports[$department->id] = $this->generateMonthlySalaryReport($month, $year, $department->id);
    }

    // Calculate comparison metrics
    $comparisonData = [];
    foreach ($departmentReports as $departmentId => $report) {
      $comparisonData[] = [
        'department_id' => $departmentId,
        'department_name' => $report['department_name'],
        'employee_count' => $report['total_records'],
        'total_net_amount' => $report['total_net_amount'],
        'avg_net_amount' => $report['avg_net_amount'],
        'deductions_percentage' => $report['total_base_amount'] > 0 ? round(($report['total_deductions'] / $report['total_base_amount']) * 100, 1) : 0,
        'bonuses_percentage' => $report['total_base_amount'] > 0 ? round(($report['total_bonuses'] / $report['total_base_amount']) * 100, 1) : 0,
        'overtime_percentage' => $report['total_base_amount'] > 0 ? round(($report['total_overtime_pay'] / $report['total_base_amount']) * 100, 1) : 0,
      ];
    }

    return [
      'month' => $month,
      'year' => $year,
      'formatted_period' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
      'department_reports' => $departmentReports,
      'comparison_data' => $comparisonData,
    ];
  }

  /**
   * Generate leave report.
   *
   * @param int|null $month
   * @param int|null $year
   * @param int|null $departmentId
   * @return array<string, mixed>
   */
  public function generateLeaveReport(?int $month = null, ?int $year = null, ?int $departmentId = null): array
  {
    $month = $month ?? Carbon::now()->month;
    $year = $year ?? Carbon::now()->year;

    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    $query = LeaveRequest::whereBetween('start_date', [$startDate, $endDate])
      ->orWhereBetween('end_date', [$startDate, $endDate])
      ->with(['user.employeeProfile.department']);

    if ($departmentId) {
      $query->whereHas('user.employeeProfile', function ($q) use ($departmentId) {
        $q->where('department_id', $departmentId);
      });
    }

    $leaveRequests = $query->get();

    // Group by status
    $statusCounts = $leaveRequests->groupBy('status')
      ->map(function ($requests) {
        return $requests->count();
      })
      ->toArray();

    // Group by leave type
    $leaveTypeCounts = $leaveRequests->groupBy('leave_type')
      ->map(function ($requests) {
        return $requests->count();
      })
      ->toArray();

    // Group by department
    $departmentStats = [];
    if (!$departmentId) {
      $departmentStats = $leaveRequests->groupBy(function ($request) {
        return $request->user->employeeProfile->department->name ?? 'Unknown';
      })->map(function ($deptRequests) {
        return [
          'count' => $deptRequests->count(),
          'total_days' => $deptRequests->sum('duration_days'),
          'approved_count' => $deptRequests->where('status', 'approved')->count(),
          'rejected_count' => $deptRequests->where('status', 'rejected')->count(),
          'pending_count' => $deptRequests->where('status', 'pending')->count(),
          'by_type' => $deptRequests->groupBy('leave_type')
            ->map(function ($typeRequests) {
              return $typeRequests->count();
            })
            ->toArray(),
        ];
      })->toArray();
    }

    return [
      'month' => $month,
      'year' => $year,
      'formatted_period' => $startDate->format('F Y'),
      'department_id' => $departmentId,
      'department_name' => $departmentId ? Department::find($departmentId)->name : 'All Departments',
      'total_requests' => $leaveRequests->count(),
      'total_days' => $leaveRequests->sum('duration_days'),
      'status_counts' => $statusCounts,
      'leave_type_counts' => $leaveTypeCounts,
      'department_stats' => $departmentStats,
      'requests' => $leaveRequests->map(function ($request) {
        return [
          'id' => $request->id,
          'user_id' => $request->user_id,
          'employee_name' => $request->user->name,
          'department' => $request->user->employeeProfile->department->name ?? 'Unknown',
          'position' => $request->user->employeeProfile->position ?? 'Unknown',
          'start_date' => $request->start_date->format('Y-m-d'),
          'end_date' => $request->end_date->format('Y-m-d'),
          'duration_days' => $request->duration_days,
          'leave_type' => $request->leave_type,
          'reason' => $request->reason,
          'status' => $request->status,
          'created_at' => $request->created_at->format('Y-m-d H:i:s'),
        ];
      }),
    ];
  }

  /**
   * Export report data to CSV.
   *
   * @param string $type
   * @param array<string, mixed> $data
   * @return string
   */
  public function exportReportToCsv(string $type, array $data): string
  {
    $csvData = [];

    switch ($type) {
      case 'daily-attendance':
        $csvData[] = ['Date', 'Employee', 'Department', 'Position', 'Check In', 'Check Out', 'Late Minutes', 'Early Departure Minutes', 'Status', 'Notes'];
        foreach ($data['logs'] as $log) {
          $csvData[] = [
            $data['formatted_date'],
            $log['employee_name'],
            $log['department'],
            $log['position'],
            $log['check_in_time'],
            $log['check_out_time'] ?? 'Not checked out',
            $log['late_minutes'],
            $log['early_departure_minutes'],
            $log['status'],
            $log['notes'] ?? '',
          ];
        }
        break;

      case 'monthly-attendance':
        $csvData[] = ['Month', 'Employee', 'Department', 'Position', 'Present Days', 'Absent Days', 'Late Days', 'Early Departure Days', 'Attendance Rate (%)', 'Punctuality Rate (%)'];
        foreach ($data['employee_stats'] as $stat) {
          $csvData[] = [
            $data['formatted_period'],
            $stat['employee_name'],
            $stat['department'],
            $stat['position'],
            $stat['present_days'],
            $stat['absent_days'],
            $stat['late_days'],
            $stat['early_departure_days'],
            $stat['attendance_rate'],
            $stat['punctuality_rate'],
          ];
        }
        break;

      case 'monthly-salary':
        $csvData[] = ['Month', 'Employee', 'Department', 'Position', 'Base Amount', 'Deductions', 'Bonuses', 'Overtime Pay', 'Net Amount', 'Status'];
        foreach ($data['records'] as $record) {
          $csvData[] = [
            $data['formatted_period'],
            $record['employee_name'],
            $record['department'],
            $record['position'],
            $record['base_amount'],
            $record['deductions'],
            $record['bonuses'],
            $record['overtime_pay'],
            $record['net_amount'],
            $record['status'],
          ];
        }
        break;

      case 'leave':
        $csvData[] = ['Month', 'Employee', 'Department', 'Position', 'Start Date', 'End Date', 'Duration (Days)', 'Leave Type', 'Reason', 'Status'];
        foreach ($data['requests'] as $request) {
          $csvData[] = [
            $data['formatted_period'],
            $request['employee_name'],
            $request['department'],
            $request['position'],
            $request['start_date'],
            $request['end_date'],
            $request['duration_days'],
            $request['leave_type'],
            $request['reason'],
            $request['status'],
          ];
        }
        break;
    }

    $output = fopen('php://temp', 'r+');
    foreach ($csvData as $row) {
      fputcsv($output, $row);
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv;
  }
}
