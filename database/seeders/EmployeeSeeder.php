<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Get departments
    $departments = Department::all();

    // Get users without employee profiles
    $users = User::whereDoesntHave('employeeProfile')->get();

    foreach ($users as $user) {
      // Assign random department
      $department = $departments->random();

      // Determine position based on role
      $position = 'Employee';
      $baseSalary = rand(30000, 50000);
      $hourlyRate = round($baseSalary / (52 * 40), 2); // Approximate hourly rate

      if ($user->hasRole('admin')) {
        $position = 'CEO';
        $baseSalary = 120000;
        $hourlyRate = round($baseSalary / (52 * 40), 2);
      } elseif ($user->hasRole('hr')) {
        $position = 'HR Manager';
        $baseSalary = 80000;
        $hourlyRate = round($baseSalary / (52 * 40), 2);
      } elseif ($user->hasRole('manager')) {
        $position = $department->name . ' Manager';
        $baseSalary = 90000;
        $hourlyRate = round($baseSalary / (52 * 40), 2);
      }

      // Random hire date between 1-5 years ago
      $hireDate = Carbon::now()->subDays(rand(30, 1825));

      Employee::create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'position' => $position,
        'hire_date' => $hireDate,
        'base_salary' => $baseSalary,
        'hourly_rate' => $hourlyRate,
        'status' => 'active',
      ]);
    }
  }
}
