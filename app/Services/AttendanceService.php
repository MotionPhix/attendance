<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
  /**
   * Record a check-in for a user.
   *
   * @param User $user
   * @param Carbon|null $checkInTime
   * @return AttendanceLog
   */
  public function checkIn(User $user, ?Carbon $checkInTime = null): AttendanceLog
  {
    // Use provided time or current time
    $checkInTime = $checkInTime ?? now();

    // Check if user has already checked in today
    if (AttendanceLog::hasCheckedInToday($user->id)) {
      throw new \Exception('You have already checked in today.');
    }

    // Get user's work schedule for today
    $workSchedule = $user->getTodayWorkSchedule();

    if (!$workSchedule) {
      // If no schedule is found, use default values
      $scheduledStartTime = Carbon::parse('09:00:00');
    } else {
      $scheduledStartTime = Carbon::parse($workSchedule->start_time);
    }

    // Calculate late minutes (with 5-minute tolerance)
    $lateMinutes = AttendanceLog::calculateLateMinutes(
      $checkInTime,
      $scheduledStartTime,
      5 // 5-minute tolerance
    );

    // Create attendance log
    return AttendanceLog::create([
      'user_id' => $user->id,
      'check_in_time' => $checkInTime,
      'late_minutes' => $lateMinutes,
      'status' => $lateMinutes > 0 ? 'late' : 'on_time',
    ]);
  }

  /**
   * Record a check-out for a user.
   *
   * @param User $user
   * @param Carbon|null $checkOutTime
   * @return AttendanceLog
   */
  public function checkOut(User $user, ?Carbon $checkOutTime = null): AttendanceLog
  {
    // Use provided time or current time
    $checkOutTime = $checkOutTime ?? now();

    // Get active session
    $attendanceLog = AttendanceLog::getActiveSession($user->id);

    if (!$attendanceLog) {
      throw new \Exception('No active check-in found.');
    }

    // Get user's work schedule for today
    $workSchedule = $user->getTodayWorkSchedule();

    if (!$workSchedule) {
      // If no schedule is found, use default values
      $scheduledEndTime = Carbon::parse('17:00:00');
    } else {
      $scheduledEndTime = Carbon::parse($workSchedule->end_time);
    }

    // Calculate early departure minutes
    $earlyDepartureMinutes = AttendanceLog::calculateEarlyDepartureMinutes(
      $checkOutTime,
      $scheduledEndTime
    );

    // Update attendance log
    $attendanceLog->update([
      'check_out_time' => $checkOutTime,
      'early_departure_minutes' => $earlyDepartureMinutes,
      'status' => $this->determineAttendanceStatus($attendanceLog->late_minutes, $earlyDepartureMinutes),
    ]);

    return $attendanceLog;
  }

  /**
   * Determine attendance status based on late and early departure minutes.
   *
   * @param int $lateMinutes
   * @param int $earlyDepartureMinutes
   * @return string
   */
  protected function determineAttendanceStatus(int $lateMinutes, int $earlyDepartureMinutes): string
  {
    if ($lateMinutes > 0 && $earlyDepartureMinutes > 0) {
      return 'late_and_early_departure';
    } elseif ($lateMinutes > 0) {
      return 'late';
    } elseif ($earlyDepartureMinutes > 0) {
      return 'early_departure';
    } else {
      return 'complete';
    }
  }

  /**
   * Get attendance statistics for a user.
   *
   * @param User $user
   * @param string $period
   * @return array<string, mixed>
   */
  public function getAttendanceStats(User $user, string $period = 'daily'): array
  {
    switch ($period) {
      case 'weekly':
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();
        break;
      case 'monthly':
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        break;
      case 'daily':
      default:
        $startDate = now()->startOfDay();
        $endDate = now()->endOfDay();
        break;
    }

    return $this->getAttendanceSummary($user->id, $startDate, $endDate);
  }

  /**
   * Get attendance summary for a user within a date range.
   *
   * @param int $userId
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return array<string, mixed>
   */
  public function getAttendanceSummary(int $userId, Carbon $startDate, Carbon $endDate): array
  {
    return AttendanceLog::getSummary($userId, $startDate, $endDate);
  }

  /**
   * Get department attendance report.
   *
   * @param int $departmentId
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return array<string, mixed>
   */
  public function getDepartmentAttendanceReport(int $departmentId, Carbon $startDate, Carbon $endDate): array
  {
    $users = User::whereHas('employeeProfile', function ($query) use ($departmentId) {
      $query->where('department_id', $departmentId);
    })->get();

    $report = [
      'department_id' => $departmentId,
      'period' => [
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
      ],
      'summary' => [
        'total_employees' => $users->count(),
        'average_attendance_rate' => 0,
        'total_late_arrivals' => 0,
        'total_early_departures' => 0,
      ],
      'employees' => [],
    ];

    $totalAttendanceRate = 0;
    $totalLateArrivals = 0;
    $totalEarlyDepartures = 0;

    foreach ($users as $user) {
      $summary = AttendanceLog::getSummary($user->id, $startDate, $endDate);

      $report['employees'][] = [
        'user_id' => $user->id,
        'name' => $user->name,
        'attendance_rate' => $summary['attendance_rate'],
        'present_days' => $summary['present_days'],
        'absent_days' => $summary['absent_days'],
        'late_arrivals' => $summary['late_arrivals'],
        'early_departures' => $summary['early_departures'],
      ];

      $totalAttendanceRate += $summary['attendance_rate'];
      $totalLateArrivals += $summary['late_arrivals'];
      $totalEarlyDepartures += $summary['early_departures'];
    }

    // Calculate averages
    if ($users->count() > 0) {
      $report['summary']['average_attendance_rate'] = round($totalAttendanceRate / $users->count(), 1);
      $report['summary']['total_late_arrivals'] = $totalLateArrivals;
      $report['summary']['total_early_departures'] = $totalEarlyDepartures;
    }

    return $report;
  }
}
