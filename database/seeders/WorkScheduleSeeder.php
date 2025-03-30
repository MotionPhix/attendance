<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get all departments
    $departments = Department::all();

    foreach ($departments as $department) {
      // Create standard 9-5 schedule
      WorkSchedule::create([
        'department_id' => $department->id,
        'name' => 'Standard 9-5',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'break_duration' => 60, // 1 hour lunch break
        'is_default' => true,
      ]);

      // Create flexible schedule
      WorkSchedule::create([
        'department_id' => $department->id,
        'name' => 'Flexible Hours',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'break_duration' => 60,
        'is_default' => false,
      ]);

      // Create late shift for some departments
      if (in_array($department->name, ['Information Technology', 'Customer Service', 'Operations'])) {
        WorkSchedule::create([
          'department_id' => $department->id,
          'name' => 'Late Shift',
          'start_time' => '12:00',
          'end_time' => '20:00',
          'break_duration' => 60,
          'is_default' => false,
        ]);
      }
    }
  }
}
