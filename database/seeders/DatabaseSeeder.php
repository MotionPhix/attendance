<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // Call other seeders
    $this->call([
      RoleAndPermissionSeeder::class,
      DepartmentSeeder::class,
      WorkScheduleSeeder::class,
      AchievementSeeder::class,
      SettingsSeeder::class,
    ]);

    // Create admin user
    $admin = User::create([
      'name' => 'Admin User',
      'email' => 'admin@example.com',
      'password' => Hash::make('password'),
    ]);
    $admin->assignRole('admin');

    // Create HR user
    $hr = User::create([
      'name' => 'HR Manager',
      'email' => 'hr@example.com',
      'password' => Hash::make('password'),
    ]);
    $hr->assignRole('hr');

    // Create department manager
    $manager = User::create([
      'name' => 'Department Manager',
      'email' => 'manager@example.com',
      'password' => Hash::make('password'),
    ]);
    $manager->assignRole('manager');

    // Create regular employee
    $employee = User::create([
      'name' => 'Test Employee',
      'email' => 'employee@example.com',
      'password' => Hash::make('password'),
    ]);
    $employee->assignRole('employee');

    // Create employee profiles for users
    $this->call(EmployeeProfileSeeder::class);

    // Create test data if not in production
    if (app()->environment() !== 'production') {
      // Create more test users
      User::factory(20)->create()->each(function ($user) {
        $user->assignRole('employee');
      });

      // Create test data
      $this->call([
        AttendanceLogSeeder::class,
        // LeaveRequestSeeder::class,
        // GoalSeeder::class,
        // FeedbackSeeder::class,
        // MoodLogSeeder::class,
        // SalaryRecordSeeder::class,
      ]);
    }
  }
}
