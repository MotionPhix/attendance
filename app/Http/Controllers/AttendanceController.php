<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceController extends Controller
{
  /**
   * The attendance service instance.
   *
   * @var AttendanceService
   */
  protected $attendanceService;

  /**
   * Create a new controller instance.
   *
   * @param AttendanceService $attendanceService
   * @return void
   */
  public function __construct(AttendanceService $attendanceService)
  {
    $this->attendanceService = $attendanceService;
  }

  /**
   * Display the daily attendance page.
   *
   * @return \Inertia\Response
   */
  public function daily()
  {
    $user = Auth::user();
    $stats = $this->attendanceService->getAttendanceStats($user, 'daily');
    $activeSession = AttendanceLog::getActiveSession($user->id);

    return Inertia::render('Attendance/Daily', [
      'stats' => $stats,
      'activeSession' => $activeSession,
      'canCheckIn' => !AttendanceLog::hasCheckedInToday($user->id),
      'canCheckOut' => $activeSession !== null,
    ]);
  }

  /**
   * Display the weekly attendance page.
   *
   * @return \Inertia\Response
   */
  public function weekly()
  {
    $user = Auth::user();
    $stats = $this->attendanceService->getAttendanceStats($user, 'weekly');

    // Get daily breakdown for the week
    $now = Carbon::now();
    $startOfWeek = $now->copy()->startOfWeek();
    $endOfWeek = $now->copy()->endOfWeek();

    $dailyLogs = [];
    $current = $startOfWeek->copy();

    while ($current->lte($endOfWeek)) {
      $dayStart = $current->copy()->startOfDay();
      $dayEnd = $current->copy()->endOfDay();

      $log = AttendanceLog::where('user_id', $user->id)
        ->whereBetween('check_in_time', [$dayStart, $dayEnd])
        ->first();

      $dailyLogs[] = [
        'date' => $current->format('Y-m-d'),
        'day' => $current->format('D'),
        'is_weekend' => $current->isWeekend(),
        'is_today' => $current->isToday(),
        'attendance' => $log ? [
          'check_in' => $log->check_in_time?->format('H:i'),
          'check_out' => $log->check_out_time?->format('H:i'),
          'status' => $log->status,
          'late_minutes' => $log->late_minutes,
          'early_departure_minutes' => $log->early_departure_minutes,
        ] : null,
      ];

      $current->addDay();
    }

    return Inertia::render('Attendance/Weekly', [
      'stats' => $stats,
      'dailyLogs' => $dailyLogs,
    ]);
  }

  /**
   * Display the monthly attendance page.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function monthly(Request $request)
  {
    $user = Auth::user();

    // Get month and year from request or use current
    $month = $request->input('month', now()->month);
    $year = $request->input('year', now()->year);

    // Create date objects
    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    // Get stats for the selected month
    $stats = AttendanceLog::getSummary($user->id, $startDate, $endDate);

    // Get daily breakdown for the month
    $dailyLogs = [];
    $current = $startDate->copy();

    while ($current->lte($endDate)) {
      $dayStart = $current->copy()->startOfDay();
      $dayEnd = $current->copy()->endOfDay();

      $log = AttendanceLog::where('user_id', $user->id)
        ->whereBetween('check_in_time', [$dayStart, $dayEnd])
        ->first();

      $dailyLogs[] = [
        'date' => $current->format('Y-m-d'),
        'day' => $current->format('d'),
        'weekday' => $current->format('D'),
        'is_weekend' => $current->isWeekend(),
        'is_today' => $current->isToday(),
        'attendance' => $log ? [
          'check_in' => $log->check_in_time?->format('H:i'),
          'check_out' => $log->check_out_time?->format('H:i'),
          'status' => $log->status,
          'late_minutes' => $log->late_minutes,
          'early_departure_minutes' => $log->early_departure_minutes,
        ] : null,
      ];

      $current->addDay();
    }

    // Get attendance trends
    $trends = $this->attendanceService->getAttendanceTrends($user, 6);

    return Inertia::render('Attendance/Monthly', [
      'stats' => $stats,
      'dailyLogs' => $dailyLogs,
      'trends' => $trends,
      'currentMonth' => [
        'month' => (int) $month,
        'year' => (int) $year,
        'name' => $startDate->format('F Y'),
      ],
    ]);
  }

  /**
   * Record a check-in.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function checkIn(Request $request)
  {
    try {
      $user = Auth::user();
      $notes = $request->input('notes');

      $this->attendanceService->checkIn($user, null, $notes);

      return redirect()->back()->with('success', 'Check-in recorded successfully.');
    } catch (\Exception $e) {
      return redirect()->back()->with('error', $e->getMessage());
    }
  }

  /**
   * Record a check-out.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function checkOut(Request $request)
  {
    try {
      $user = Auth::user();
      $notes = $request->input('notes');

      $this->attendanceService->checkOut($user, null, $notes);

      return redirect()->back()->with('success', 'Check-out recorded successfully.');
    } catch (\Exception $e) {
      return redirect()->back()->with('error', $e->getMessage());
    }
  }

  /**
   * Display the user's attendance history.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function history(Request $request)
  {
    $user = Auth::user();

    // Pagination parameters
    $perPage = $request->input('per_page', 15);
    $page = $request->input('page', 1);

    // Date range filter
    $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

    // Build query
    $query = AttendanceLog::where('user_id', $user->id)
      ->orderBy('check_in_time', 'desc');

    // Apply date filters if provided
    if ($startDate && $endDate) {
      $query->whereBetween('check_in_time', [$startDate->startOfDay(), $endDate->endOfDay()]);
    } elseif ($startDate) {
      $query->where('check_in_time', '>=', $startDate->startOfDay());
    } elseif ($endDate) {
      $query->where('check_in_time', '<=', $endDate->endOfDay());
    }

    // Get paginated results
    $logs = $query->paginate($perPage);

    return Inertia::render('Attendance/History', [
      'logs' => $logs,
      'filters' => [
        'start_date' => $startDate?->toDateString(),
        'end_date' => $endDate?->toDateString(),
      ],
    ]);
  }
}
