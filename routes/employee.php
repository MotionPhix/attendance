<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MoodLogController;
use App\Http\Controllers\SalaryController;
use Illuminate\Support\Facades\Route;

// Authenticated user routes
Route::middleware(['auth', 'verified', 'redirect.role'])->group(function () {
  // Dashboard
  Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

  // Attendance
  Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/daily', [AttendanceController::class, 'daily'])->name('daily');
    Route::get('/weekly', [AttendanceController::class, 'weekly'])->name('weekly');
    Route::get('/monthly', [AttendanceController::class, 'monthly'])->name('monthly');
    Route::get('/history', [AttendanceController::class, 'history'])->name('history');
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
  });

  // Leave Requests
  Route::resource('leave-requests', LeaveRequestController::class);

  // Salary
  Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
  Route::get('/salary/{salary}/payslip', [SalaryController::class, 'payslip'])->name('salary.payslip');

  // Goals
  Route::resource('goals', GoalController::class);

  // Feedback
  Route::resource('feedback', FeedbackController::class);

  // Mood Tracking
  Route::resource('mood-logs', MoodLogController::class)->only(['index', 'store', 'show']);

  // Achievements
  Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
});
