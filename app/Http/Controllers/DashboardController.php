<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Goal;
use App\Models\LeaveRequest;
use App\Models\MoodLog;
use App\Models\UserAchievement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
  /**
   * Display the employee dashboard.
   *
   * @return \Inertia\Response
   */
  public function index()
  {
    $user = Auth::user();

    // Get today's date
    $today = Carbon::today();
    $currentMonth = $today->month;
    $currentYear = $today->year;

    // Check if user has checked in today
    $todayAttendance = AttendanceLog::where('user_id', $user->id)
      ->whereDate('check_in_time', $today)
      ->first();

    // Get active session (if user has checked in but not checked out)
    $activeSession = AttendanceLog::getActiveSession($user->id);

    // Get attendance statistics for current month
    $startDate = Carbon::createFromDate($currentYear, $currentMonth, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($currentYear, $currentMonth, 1)->endOfMonth();

    $attendanceStats = AttendanceLog::getSummary($user->id, $startDate, $endDate);

    // Get attendance trends for last 7 days
    $attendanceTrends = [];
    for ($i = 6; $i >= 0; $i--) {
      $date = Carbon::today()->subDays($i);

      $log = AttendanceLog::where('user_id', $user->id)
        ->whereDate('check_in_time', $date)
        ->first();

      $attendanceTrends[] = [
        'date' => $date->format('Y-m-d'),
        'day' => $date->format('D'),
        'check_in' => $log ? $log->check_in_time->format('H:i') : null,
        'check_out' => $log && $log->check_out_time ? $log->check_out_time->format('H:i') : null,
        'status' => $log ? $log->status : null,
        'late_minutes' => $log ? $log->late_minutes : 0,
        'early_departure_minutes' => $log ? $log->early_departure_minutes : 0,
      ];
    }

    // Get pending leave requests
    $pendingLeaveRequests = LeaveRequest::where('user_id', $user->id)
      ->where('status', 'pending')
      ->orderBy('start_date')
      ->limit(3)
      ->get();

    // Get upcoming leave
    $upcomingLeave = LeaveRequest::where('user_id', $user->id)
      ->where('status', 'approved')
      ->where('start_date', '>=', $today)
      ->orderBy('start_date')
      ->first();

    // Get active goals
    $activeGoals = Goal::where('user_id', $user->id)
      ->whereIn('status', ['not_started', 'in_progress'])
      ->orderBy('target_date')
      ->limit(3)
      ->get();

    // Get recently completed goals
    $completedGoals = Goal::where('user_id', $user->id)
      ->where('status', 'completed')
      ->orderBy('updated_at', 'desc')
      ->limit(3)
      ->get();

    // Get latest mood log
    $latestMood = MoodLog::where('user_id', $user->id)
      ->latest()
      ->first();

    // Get mood trends for last 7 days
    $moodTrends = [];
    for ($i = 6; $i >= 0; $i--) {
      $date = Carbon::today()->subDays($i);

      $log = MoodLog::where('user_id', $user->id)
        ->whereDate('created_at', $date)
        ->first();

      $moodTrends[] = [
        'date' => $date->format('Y-m-d'),
        'day' => $date->format('D'),
        'mood_level' => $log ? $log->mood_level : null,
      ];
    }

    // Get recent achievements
    $recentAchievements = UserAchievement::where('user_id', $user->id)
      ->with('achievement')
      ->orderBy('earned_at', 'desc')
      ->limit(3)
      ->get();

    // Get motivational quote (could be from an API or database)
    $motivationalQuote = $this->getMotivationalQuote();

    return Inertia::render('Dashboard', [
      'attendanceStats' => $attendanceStats,
      'attendanceTrends' => $attendanceTrends,
      'todayAttendance' => $todayAttendance,
      'activeSession' => $activeSession,
      'canCheckIn' => !$todayAttendance,
      'canCheckOut' => $activeSession !== null,
      'pendingLeaveRequests' => $pendingLeaveRequests,
      'upcomingLeave' => $upcomingLeave,
      'activeGoals' => $activeGoals,
      'completedGoals' => $completedGoals,
      'latestMood' => $latestMood,
      'moodTrends' => $moodTrends,
      'recentAchievements' => $recentAchievements,
      'motivationalQuote' => $motivationalQuote,
      'currentDate' => $today->format('F d, Y'),
    ]);
  }

  /**
   * Get a motivational quote.
   *
   * @return array<string, string>
   */
  private function getMotivationalQuote(): array
  {
    $quotes = [
      [
        'text' => 'Success is not final, failure is not fatal: It is the courage to continue that counts.',
        'author' => 'Winston Churchill',
      ],
      [
        'text' => 'Believe you can and you\'re halfway there.',
        'author' => 'Theodore Roosevelt',
      ],
      [
        'text' => 'The future belongs to those who believe in the beauty of their dreams.',
        'author' => 'Eleanor Roosevelt',
      ],
      [
        'text' => 'Don\'t watch the clock; do what it does. Keep going.',
        'author' => 'Sam Levenson',
      ],
      [
        'text' => 'The only way to do great work is to love what you do.',
        'author' => 'Steve Jobs',
      ],
      [
        'text' => 'Your time is limited, don\'t waste it living someone else\'s life.',
        'author' => 'Steve Jobs',
      ],
      [
        'text' => 'The best way to predict the future is to create it.',
        'author' => 'Peter Drucker',
      ],
      [
        'text' => 'Success is walking from failure to failure with no loss of enthusiasm.',
        'author' => 'Winston Churchill',
      ],
      [
        'text' => 'The secret of getting ahead is getting started.',
        'author' => 'Mark Twain',
      ],
      [
        'text' => 'The harder you work for something, the greater you\'ll feel when you achieve it.',
        'author' => 'Anonymous',
      ],
    ];

    return $quotes[array_rand($quotes)];
  }
}
