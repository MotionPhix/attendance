<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia
{
  use InteractsWithMedia;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'department_id',
    'position',
    'employee_id',
    'join_date',
    'status',
    'phone',
    'address',
    'emergency_contact',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'join_date' => 'date',
    'emergency_contact' => 'array',
  ];

  /**
   * Get the user that owns the profile.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the department that the employee belongs to.
   */
  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  /**
   * Register media collections for the employee profile.
   */
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('profile_image')
      ->singleFile();
  }

  /**
   * Calculate the per-minute wage for salary deductions.
   *
   * @return float
   */
  public function getPerMinuteRate(): float
  {
    if (!$this->hourly_rate) {
      // If hourly rate is not set, calculate from monthly salary
      // Assuming 22 working days per month, 8 hours per day
      return $this->base_salary / (22 * 8 * 60);
    }

    // Otherwise use the hourly rate
    return $this->hourly_rate / 60;
  }

  /**
   * Calculate salary for a given month with deductions and bonuses.
   *
   * @param int $month
   * @param int $year
   * @return array<string, mixed>
   */
  public function calculateSalary(int $month, int $year): array
  {
    $baseAmount = $this->base_salary;
    $deductions = 0;
    $bonuses = 0;
    $overtimePay = 0;

    // Get work schedule to determine standard hours
    $defaultSchedule = WorkSchedule::where('department_id', $this->department_id)
      ->where('is_default', true)
      ->first();

    $standardDailyHours = $defaultSchedule
      ? (strtotime($defaultSchedule->end_time) - strtotime($defaultSchedule->start_time)) / 3600 - ($defaultSchedule->break_duration / 60)
      : 8; // Default to 8 hours if no schedule is set

    // Calculate total standard hours for the month
    $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
    $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();

    // Get attendance logs for the month
    $attendanceLogs = AttendanceLog::where('user_id', $this->user_id)
      ->whereBetween('check_in_time', [$startDate, $endDate])
      ->get();

    foreach ($attendanceLogs as $log) {
      // Calculate late minutes deductions
      if ($log->late_minutes > 0) {
        $deductions += $log->late_minutes * $this->getPerMinuteRate();
      }

      // Calculate early departure deductions
      if ($log->early_departure_minutes > 0) {
        $deductions += $log->early_departure_minutes * $this->getPerMinuteRate();
      }

      // Calculate overtime if check-out time exists
      if ($log->check_out_time) {
        $hoursWorked = (strtotime($log->check_out_time) - strtotime($log->check_in_time)) / 3600;

        // If worked more than standard hours, calculate overtime
        if ($hoursWorked > $standardDailyHours) {
          $overtimeHours = $hoursWorked - $standardDailyHours;
          // Assuming overtime is paid at 1.5x hourly rate
          $overtimePay += $overtimeHours * $this->hourly_rate * 1.5;
        }
      }
    }

    // Calculate net amount
    $netAmount = $baseAmount - $deductions + $bonuses + $overtimePay;

    return [
      'base_amount' => $baseAmount,
      'deductions' => $deductions,
      'bonuses' => $bonuses,
      'overtime_pay' => $overtimePay,
      'net_amount' => $netAmount,
    ];
  }
}
