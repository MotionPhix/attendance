<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceLogSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get employees
    $employees = User::role('employee')->with('employeeProfile.department')->get();

    // Generate attendance for the last 30 days
    $startDate = Carbon::now()->subDays(30);
    $endDate = Carbon::now();

    foreach ($employees as $employee) {
      // Skip if no employee profile
      if (!$employee->employeeProfile) {
        continue;
      }

      // Get department's default work schedule
      $workSchedule = WorkSchedule::where('department_id', $employee->employeeProfile->department_id)
        ->where('is_default', true)
        ->first();

      if (!$workSchedule) {
        continue;
      }

      $currentDate = $startDate->copy();

      while ($currentDate->lte($endDate)) {
        // Skip weekends
        if ($currentDate->isWeekend()) {
          $currentDate->addDay();
          continue;
        }

        // 90% chance of attendance
        if (rand(1, 100) <= 90) {
          // Parse schedule times
          $scheduledStartTime = Carbon::parse($workSchedule->start_time);
          $scheduledEndTime = Carbon::parse($workSchedule->end_time);

          // Set check-in time (80% on time, 20% late)
          $lateMinutes = 0;
          if (rand(1, 100) <= 80) {
            // On time (within 5 minutes before scheduled start)
            $checkInTime = $currentDate->copy()->setHour($scheduledStartTime->hour)
              ->setMinute($scheduledStartTime->minute)
              ->subMinutes(rand(0, 5));
          } else {
            // Late (up to 30 minutes)
            $lateMinutes = rand(1, 30);
            $checkInTime = $currentDate->copy()->setHour($scheduledStartTime->hour)
              ->setMinute($scheduledStartTime->minute)
              ->addMinutes($lateMinutes);
          }

          // Set check-out time (85% on time, 15% early departure)
          $earlyDepartureMinutes = 0;
          if (rand(1, 100) <= 85) {
            // On time or overtime (up to 60 minutes)
            $checkOutTime = $currentDate->copy()->setHour($scheduledEndTime->hour)
              ->setMinute($scheduledEndTime->minute)
              ->addMinutes(rand(0, 60));
          } else {
            // Early departure (up to 30 minutes)
            $earlyDepartureMinutes = rand(1, 30);
            $checkOutTime = $currentDate->copy()->setHour($scheduledEndTime->hour)
              ->setMinute($scheduledEndTime->minute)
              ->subMinutes($earlyDepartureMinutes);
          }

          // Determine status
          $status = 'on_time';
          if ($lateMinutes > 0 && $earlyDepartureMinutes > 0) {
            $status = 'late_and_early_departure';
          } elseif ($lateMinutes > 0) {
            $status = 'late';
          } elseif ($earlyDepartureMinutes > 0) {
            $status = 'early_departure';
          }

          // Create attendance log
          AttendanceLog::create([
            'user_id' => $employee->id,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'status' => $status,
          ]);
        }

        $currentDate->addDay();
      }
    }
  }
}
