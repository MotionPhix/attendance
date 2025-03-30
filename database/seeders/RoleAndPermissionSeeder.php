<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create permissions
    // User management permissions
    Permission::create(['name' => 'view users']);
    Permission::create(['name' => 'create users']);
    Permission::create(['name' => 'edit users']);
    Permission::create(['name' => 'delete users']);

    // Department management permissions
    Permission::create(['name' => 'view departments']);
    Permission::create(['name' => 'create departments']);
    Permission::create(['name' => 'edit departments']);
    Permission::create(['name' => 'delete departments']);

    // Work schedule management permissions
    Permission::create(['name' => 'view work schedules']);
    Permission::create(['name' => 'create work schedules']);
    Permission::create(['name' => 'edit work schedules']);
    Permission::create(['name' => 'delete work schedules']);

    // Attendance management permissions
    Permission::create(['name' => 'view attendance']);
    Permission::create(['name' => 'view all attendance']);
    Permission::create(['name' => 'edit attendance']);

    // Leave request management permissions
    Permission::create(['name' => 'view leave requests']);
    Permission::create(['name' => 'create leave requests']);
    Permission::create(['name' => 'approve leave requests']);
    Permission::create(['name' => 'reject leave requests']);

    // Salary management permissions
    Permission::create(['name' => 'view salaries']);
    Permission::create(['name' => 'view all salaries']);
    Permission::create(['name' => 'generate salaries']);
    Permission::create(['name' => 'mark salaries as paid']);

    // Goal management permissions
    Permission::create(['name' => 'view goals']);
    Permission::create(['name' => 'create goals']);
    Permission::create(['name' => 'edit goals']);
    Permission::create(['name' => 'delete goals']);

    // Feedback management permissions
    Permission::create(['name' => 'view feedback']);
    Permission::create(['name' => 'view all feedback']);
    Permission::create(['name' => 'create feedback']);  // Added missing permission
    Permission::create(['name' => 'edit feedback']);    // Added missing permission
    Permission::create(['name' => 'delete feedback']);  // Added missing permission
    Permission::create(['name' => 'respond to feedback']);

    // Mood log permissions
    Permission::create(['name' => 'view mood logs']);
    Permission::create(['name' => 'view all mood logs']);
    Permission::create(['name' => 'create mood logs']);  // Added missing permission

    // Achievement permissions
    Permission::create(['name' => 'view achievements']);
    Permission::create(['name' => 'create achievements']);
    Permission::create(['name' => 'edit achievements']);
    Permission::create(['name' => 'delete achievements']);
    Permission::create(['name' => 'assign achievements']);

    // Report permissions
    Permission::create(['name' => 'view reports']);
    Permission::create(['name' => 'export reports']);

    // Create roles and assign permissions
    // Admin role
    $adminRole = Role::create(['name' => 'admin']);
    $adminRole->givePermissionTo(Permission::all());

    // HR role
    $hrRole = Role::create(['name' => 'hr']);
    $hrRole->givePermissionTo([
      'view users', 'create users', 'edit users',
      'view departments',
      'view work schedules',
      'view all attendance', 'edit attendance',
      'view leave requests', 'approve leave requests', 'reject leave requests',
      'view all salaries', 'generate salaries', 'mark salaries as paid',
      'view all feedback', 'respond to feedback',
      'view all mood logs',
      'view achievements', 'assign achievements',
      'view reports', 'export reports',
    ]);

    // Manager role
    $managerRole = Role::create(['name' => 'manager']);
    $managerRole->givePermissionTo([
      'view users',
      'view departments',
      'view work schedules',
      'view all attendance',
      'view leave requests', 'approve leave requests', 'reject leave requests',
      'view all salaries',
      'view all feedback', 'respond to feedback',
      'view all mood logs',
      'view achievements',
      'view reports',
    ]);

    // Employee role
    $employeeRole = Role::create(['name' => 'employee']);
    $employeeRole->givePermissionTo([
      'view attendance',
      'view leave requests', 'create leave requests',
      'view salaries',
      'view goals', 'create goals', 'edit goals', 'delete goals',
      'view feedback', 'create feedback', 'edit feedback', 'delete feedback',
      'view mood logs', 'create mood logs',
      'view achievements',
    ]);
  }
}
