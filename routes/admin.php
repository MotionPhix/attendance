
<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\SalaryController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AchievementController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\MoodLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application.
|
*/

Route::middleware(['auth', 'verified', 'role:admin|hr|manager', 'redirect.role'])->prefix('admin')->name('admin.')->group(function () {
  // Admin Dashboard
  Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

  // Department Management
  Route::resource('departments', DepartmentController::class);
  Route::get('departments/{department}/statistics', [DepartmentController::class, 'statistics'])->name('departments.statistics');

  // Employee Management
  Route::resource('employees', EmployeeController::class);
  Route::get('employees/{employee}/attendance', [EmployeeController::class, 'attendance'])->name('employees.attendance');
  Route::get('employees/{employee}/salary', [EmployeeController::class, 'salary'])->name('employees.salary');
  Route::post('employees/{employee}/avatar', [EmployeeController::class, 'updateAvatar'])
    ->name('employees.update-avatar');

  // Work Schedule Management
  Route::resource('work-schedules', WorkScheduleController::class);
  Route::patch('work-schedules/{workSchedule}/set-default', [WorkScheduleController::class, 'setDefault'])->name('work-schedules.set-default');

  // Salary Management
  Route::get('salaries', [SalaryController::class, 'index'])->name('salaries.index');
  Route::get('salaries/generate', [SalaryController::class, 'generate'])->name('salaries.generate');
  Route::post('salaries/preview', [SalaryController::class, 'preview'])->name('salaries.preview');
  Route::post('salaries/generate', [SalaryController::class, 'processGeneration'])->name('salaries.process-generation');
  Route::get('salaries/{salary}', [SalaryController::class, 'show'])->name('salaries.show');
  Route::post('salaries/mark-as-paid', [SalaryController::class, 'markMultipleAsPaid'])->name('salaries.mark-as-paid');
  Route::post('salaries/{salary}/mark-as-paid', [SalaryController::class, 'markSingleAsPaid'])->name('salaries.mark-single-as-paid');
  Route::post('salaries/{salary}/recalculate', [SalaryController::class, 'recalculate'])->name('salaries.recalculate');
  Route::get('salaries/{salary}/payslip', [SalaryController::class, 'generatePayslip'])->name('salaries.payslip');
  Route::get('salaries/{salary}/download', [SalaryController::class, 'downloadPayslip'])->name('salaries.download');
  Route::get('salaries/department-stats', [SalaryController::class, 'departmentStats'])->name('salaries.department-stats');

  // Leave Request Management
  Route::resource('leave-requests', LeaveRequestController::class);
  Route::patch('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
  Route::patch('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');

  // Achievement Management
  Route::resource('achievements', AchievementController::class);
  Route::get('achievements/{achievement}/assign', [AchievementController::class, 'assignForm'])->name('achievements.assign-form');
  Route::post('achievements/{achievement}/assign/{user}', [AchievementController::class, 'assignToUser'])->name('achievements.assign');
  Route::delete('achievements/{achievement}/revoke/{user}', [AchievementController::class, 'revokeFromUser'])->name('achievements.revoke');

  // Feedback Management
  Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
  Route::get('feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');
  Route::patch('feedback/{feedback}/respond', [FeedbackController::class, 'respond'])->name('feedback.respond');
  Route::patch('feedback/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('feedback.status');

  // Mood Log Management
  Route::get('mood-logs', [MoodLogController::class, 'index'])->name('mood-logs.index');
  Route::get('mood-logs/support-needed', [MoodLogController::class, 'supportNeeded'])->name('mood-logs.support-needed');
  Route::get('mood-logs/{moodLog}', [MoodLogController::class, 'show'])->name('mood-logs.show');
  Route::patch('mood-logs/{moodLog}/support-provided', [MoodLogController::class, 'markSupportProvided'])->name('mood-logs.support-provided');

  // Attendance Reports
  Route::get('reports/attendance/daily', [ReportController::class, 'dailyAttendance'])->name('reports.attendance.daily');
  Route::get('reports/attendance/monthly', [ReportController::class, 'monthlyAttendance'])->name('reports.attendance.monthly');
  Route::get('reports/attendance/department', [ReportController::class, 'departmentAttendance'])->name('reports.attendance.department');
  Route::get('reports/salary/monthly', [ReportController::class, 'monthlySalary'])->name('reports.salary.monthly');
  Route::get('reports/salary/department', [ReportController::class, 'departmentSalary'])->name('reports.salary.department');
  Route::get('reports/leave', [ReportController::class, 'leaveReport'])->name('reports.leave');
  Route::get('reports/export/{type}', [ReportController::class, 'exportReport'])->name('reports.export');

  // Add these lines to routes/admin.php inside the admin group
  Route::resource('attendance', AttendanceController::class);

  // System Settings
  Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
  Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');
  Route::get('settings/attendance', [SettingController::class, 'attendanceSettings'])->name('settings.attendance');
  Route::patch('settings/attendance', [SettingController::class, 'updateAttendanceSettings'])->name('settings.attendance.update');
  Route::get('settings/leave', [SettingController::class, 'leaveSettings'])->name('settings.leave');
  Route::patch('settings/leave', [SettingController::class, 'updateLeaveSettings'])->name('settings.leave.update');
  Route::get('settings/salary', [SettingController::class, 'salarySettings'])->name('settings.salary');
  Route::patch('settings/salary', [SettingController::class, 'updateSalarySettings'])->name('settings.salary.update');
});
