<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'check_in_time',
    'check_out_time',
    'late_minutes',
    'early_departure_minutes',
    'status',
    'notes',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'check_in_time' => 'datetime',
    'check_out_time' => 'datetime',
    'late_minutes' => 'integer',
    'early_departure_minutes' => 'integer',
  ];

  /**
   * Get the user that owns the attendance log.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Calculate late minutes based on scheduled start time.
   *
   * @param Carbon $checkInTime
   * @param Carbon $scheduledStartTime
   * @param int $toleranceMinutes
   * @return int
   */
  public static function calculateLateMinutes(Carbon $checkInTime, Carbon $scheduledStartTime, int $toleranceMinutes = 5): int
  {
    // Clone the scheduled start time and add tolerance
    $toleratedStartTime = $scheduledStartTime->copy()->addMinutes($toleranceMinutes);

    // If check-in is before or equal to tolerated time, no late minutes
    if ($checkInTime->lessThanOrEqualTo($toleratedStartTime)) {
      return 0;
    }

    // Calculate late minutes
    return $checkInTime->diffInMinutes($scheduledStartTime);
  }

  /**
   * Calculate early departure minutes based on scheduled end time.
   *
   * @param Carbon $checkOutTime
   * @param Carbon $scheduledEndTime
   * @return int
   */
  public static function calculateEarlyDepartureMinutes(Carbon $checkOutTime, Carbon $scheduledEndTime): int
  {
    // If check-out is after or equal to scheduled end time, no early departure
    if ($checkOutTime->greaterThanOrEqualTo($scheduledEndTime)) {
      return 0;
    }

    // Calculate early departure minutes
    return $checkOutTime->diffInMinutes($scheduledEndTime);
  }

  /**
   * Check if the user has already checked in for today.
   *
   * @param int $userId
   * @return bool
   */
  public static function hasCheckedInToday(int $userId): bool
  {
    return self::where('user_id', $userId)
      ->whereDate('check_in_time', Carbon::today())
      ->exists();
  }

  /**
   * Get the latest attendance log for a user that doesn't have a check-out time.
   *
   * @param int $userId
   * @return AttendanceLog|null
   */
  public static function getActiveSession(int $userId): ?AttendanceLog
  {
    return self::where('user_id', $userId)
      ->whereNull('check_out_time')
      ->latest('check_in_time')
      ->first();
  }

  /**
   * Get attendance summary for a user within a date range.
   *
   * @param int $userId
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return array<string, mixed>
   */
  public static function getSummary(int $userId, Carbon $startDate, Carbon $endDate): array
  {
    $logs = self::where('user_id', $userId)
      ->whereBetween('check_in_time', [$startDate, $endDate])
      ->get();

    $totalDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
        return !$date->isWeekend(); // Skip weekends
      }, $endDate) + 1; // +1 to include end date

    $presentDays = $logs->count();
    $lateDays = $logs->where('late_minutes', '>', 0)->count();
    $earlyDepartures = $logs->where('early_departure_minutes', '>', 0)->count();
    $absentDays = $totalDays - $presentDays;

    // Calculate total hours worked
    $totalMinutesWorked = 0;
    foreach ($logs as $log) {
      if ($log->check_out_time) {
        $totalMinutesWorked += Carbon::parse($log->check_in_time)
          ->diffInMinutes(Carbon::parse($log->check_out_time));
      }
    }
    $totalHoursWorked = round($totalMinutesWorked / 60, 1);

    return [
      'period' => [
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
      ],
      'total_working_days' => $totalDays,
      'present_days' => $presentDays,
      'absent_days' => $absentDays,
      'late_arrivals' => $lateDays,
      'early_departures' => $earlyDepartures,
      'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
      'total_hours_worked' => $totalHoursWorked,
    ];
  }
}
