<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\SalaryRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
  /**
   * Display the admin dashboard.
   *
   * @return \Inertia\Response
   */
  public function index()
  {
    // Get today's date
    $today = Carbon::today();
    $currentMonth = $today->month;
    $currentYear = $today->year;

    // Employee statistics
    $totalEmployees = EmployeeProfile::count();
    $activeEmployees = EmployeeProfile::where('status', 'active')->count();
    $onLeaveEmployees = EmployeeProfile::where('status', 'on_leave')->count();
    $departmentCounts = EmployeeProfile::select('department_id', DB::raw('count(*) as employee_count'))
      ->groupBy('department_id')
      ->with('department:id,name')
      ->get()
      ->map(function ($item) {
        return [
          'department' => $item->department->name,
          'count' => $item->employee_count,
        ];
      });

    // Attendance statistics for today
    $todayAttendance = [
      'present' => AttendanceLog::whereDate('check_in_time', $today)->count(),
      'late' => AttendanceLog::whereDate('check_in_time', $today)
        ->where('late_minutes', '>', 0)
        ->count(),
      'early_departure' => AttendanceLog::whereDate('check_in_time', $today)
        ->where('early_departure_minutes', '>', 0)
        ->whereNotNull('check_out_time')
        ->count(),
      'absent' => $activeEmployees - AttendanceLog::whereDate('check_in_time', $today)->count(),
    ];

    // Monthly attendance trends
    $monthlyAttendance = [];
    for ($i = 5; $i >= 0; $i--) {
      $month = Carbon::now()->subMonths($i);
      $startDate = $month->copy()->startOfMonth();
      $endDate = $month->copy()->endOfMonth();

      $totalWorkingDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
          return !$date->isWeekend();
        }, $endDate) + 1;

      $presentDays = AttendanceLog::whereBetween('check_in_time', [$startDate, $endDate])
        ->select('user_id', DB::raw('DATE(check_in_time) as date'))
        ->distinct()
        ->count();

      $lateDays = AttendanceLog::whereBetween('check_in_time', [$startDate, $endDate])
        ->where('late_minutes', '>', 0)
        ->count();

      $earlyDepartures = AttendanceLog::whereBetween('check_in_time', [$startDate, $endDate])
        ->where('early_departure_minutes', '>', 0)
        ->whereNotNull('check_out_time')
        ->count();

      $monthlyAttendance[] = [
        'month' => $month->format('M Y'),
        'attendance_rate' => $totalWorkingDays > 0
          ? round(($presentDays / ($totalWorkingDays * $activeEmployees)) * 100, 1)
          : 0,
        'late_arrivals' => $lateDays,
        'early_departures' => $earlyDepartures,
      ];
    }

    // Salary statistics for current month
    $salarySummary = [
      'total_base_salary' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->sum('base_amount'),
      'total_deductions' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->sum('deductions'),
      'total_bonuses' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->sum('bonuses'),
      'total_overtime_pay' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->sum('overtime_pay'),
      'total_net_amount' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->sum('net_amount'),
      'processed_count' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->count(),
      'paid_count' => SalaryRecord::where('month', $currentMonth)
        ->where('year', $currentYear)
        ->where('status', 'paid')
        ->count(),
    ];

    // Recent activities
    $recentActivities = $this->getRecentActivities();

    // Pending leave requests
    $pendingLeaveRequests = LeaveRequest::with(['user'])
      ->where('status', 'pending')
      ->orderBy('created_at', 'desc')
      ->limit(5)
      ->get();

    // Top performers (employees with highest attendance rate)
    $topPerformers = $this->getTopPerformers();

    return Inertia::render('admin/Dashboard', [
      'employeeStats' => [
        'total' => $totalEmployees,
        'active' => $activeEmployees,
        'on_leave' => $onLeaveEmployees,
        'by_department' => $departmentCounts,
      ],
      'attendanceStats' => [
        'today' => $todayAttendance,
        'monthly_trends' => $monthlyAttendance,
      ],
      'salaryStats' => $salarySummary,
      'recentActivities' => $recentActivities,
      'pendingLeaveRequests' => $pendingLeaveRequests,
      'topPerformers' => $topPerformers,
      'currentDate' => $today->format('F d, Y'),
      'currentMonth' => $today->format('F Y'),
    ]);
  }

  /**
   * Get recent activities.
   *
   * @param int $limit
   * @return array
   */
  private function getRecentActivities($limit = 10)
  {
    $activities = [];

    // Recent check-ins
    $recentCheckIns = AttendanceLog::with('user')
      ->orderBy('check_in_time', 'desc')
      ->limit($limit)
      ->get()
      ->map(function ($log) {
        return [
          'type' => 'check_in',
          'user' => $log->user->name,
          'time' => $log->check_in_time,
          'details' => $log->late_minutes > 0
            ? "Late by {$log->late_minutes} minutes"
            : 'On time',
        ];
      });

    // Recent check-outs
    $recentCheckOuts = AttendanceLog::with('user')
      ->whereNotNull('check_out_time')
      ->orderBy('check_out_time', 'desc')
      ->limit($limit)
      ->get()
      ->map(function ($log) {
        return [
          'type' => 'check_out',
          'user' => $log->user->name,
          'time' => $log->check_out_time,
          'details' => $log->early_departure_minutes > 0
            ? "Left early by {$log->early_departure_minutes} minutes"
            : 'Completed full day',
        ];
      });

    // Recent leave requests
    $recentLeaveRequests = LeaveRequest::with('user')
      ->orderBy('created_at', 'desc')
      ->limit($limit)
      ->get()
      ->map(function ($request) {
        return [
          'type' => 'leave_request',
          'user' => $request->user->name,
          'time' => $request->created_at,
          'details' => "Requested {$request->duration_days} days of {$request->leave_type} leave",
          'status' => $request->status,
        ];
      });

    // Merge and sort by time
    $activities = $recentCheckIns->concat($recentCheckOuts)->concat($recentLeaveRequests)
      ->sortByDesc('time')
      ->take($limit)
      ->values()
      ->all();

    return $activities;
  }

  /**
   * Get top performers based on attendance.
   *
   * @param int $limit
   * @return array
   */
  private function getTopPerformers($limit = 5)
  {
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();

    $users = User::whereHas('employeeProfile', function ($query) {
      $query->where('status', 'active');
    })->get();

    $performers = [];

    foreach ($users as $user) {
      $stats = AttendanceLog::getSummary($user->id, $startDate, $endDate);

      $performers[] = [
        'user_id' => $user->id,
        'name' => $user->name,
        'attendance_rate' => $stats['attendance_rate'],
        'present_days' => $stats['present_days'],
        'total_days' => $stats['total_working_days'],
        'late_arrivals' => $stats['late_arrivals'],
        'early_departures' => $stats['early_departures'],
      ];
    }

    // Sort by attendance rate (desc) and then by late arrivals (asc)
    usort($performers, function ($a, $b) {
      if ($a['attendance_rate'] === $b['attendance_rate']) {
        return $a['late_arrivals'] - $b['late_arrivals'];
      }
      return $b['attendance_rate'] - $a['attendance_rate'];
    });

    return array_slice($performers, 0, $limit);
  }
}
