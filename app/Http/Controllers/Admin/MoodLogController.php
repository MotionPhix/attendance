<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MoodLog;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MoodLogController extends Controller
{
  /**
   * Display a listing of the mood logs.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $query = MoodLog::with(['user.employeeProfile.department']);

    // Mood level filter
    if ($request->has('mood_level') && $request->input('mood_level')) {
      $query->where('mood_level', $request->input('mood_level'));
    }

    // Need support filter
    if ($request->has('need_support') && $request->input('need_support') !== null) {
      $query->where('need_support', $request->input('need_support'));
    }

    // Department filter
    if ($request->has('department') && $request->input('department')) {
      $query->whereHas('user.employeeProfile', function ($q) use ($request) {
        $q->where('department_id', $request->input('department'));
      });
    }

    // Date range filter
    if ($request->has('start_date') && $request->input('start_date')) {
      $query->whereDate('created_at', '>=', $request->input('start_date'));
    }

    if ($request->has('end_date') && $request->input('end_date')) {
      $query->whereDate('created_at', '<=', $request->input('end_date'));
    }

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('notes', 'like', "%{$search}%")
          ->orWhereHas('user', function ($userQuery) use ($search) {
            $userQuery->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
          });
      });
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    if ($sortField === 'employee') {
      $query->join('users', 'mood_logs.user_id', '=', 'users.id')
        ->orderBy('users.name', $sortDirection)
        ->select('mood_logs.*');
    } elseif ($sortField === 'department') {
      $query->join('users', 'mood_logs.user_id', '=', 'users.id')
        ->join('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
        ->join('departments', 'employee_profiles.department_id', '=', 'departments.id')
        ->orderBy('departments.name', $sortDirection)
        ->select('mood_logs.*');
    } else {
      $query->orderBy($sortField, $sortDirection);
    }

    // Pagination
    $moodLogs = $query->paginate(10)
      ->withQueryString();

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get mood statistics
    $stats = [
      'total' => MoodLog::count(),
      'need_support' => MoodLog::where('need_support', true)->count(),
      'by_level' => [
        1 => MoodLog::where('mood_level', 1)->count(),
        2 => MoodLog::where('mood_level', 2)->count(),
        3 => MoodLog::where('mood_level', 3)->count(),
        4 => MoodLog::where('mood_level', 4)->count(),
        5 => MoodLog::where('mood_level', 5)->count(),
      ],
      'average' => round(MoodLog::avg('mood_level'), 1),
    ];

    // Get mood trends for last 30 days
    $trends = [];
    $startDate = Carbon::now()->subDays(29);
    $endDate = Carbon::now();

    $currentDate = $startDate->copy();
    while ($currentDate->lte($endDate)) {
      $dayLogs = MoodLog::whereDate('created_at', $currentDate)->get();

      $trends[] = [
        'date' => $currentDate->format('Y-m-d'),
        'day' => $currentDate->format('d'),
        'month' => $currentDate->format('M'),
        'count' => $dayLogs->count(),
        'average' => $dayLogs->count() > 0 ? round($dayLogs->avg('mood_level'), 1) : null,
        'need_support' => $dayLogs->where('need_support', true)->count(),
      ];

      $currentDate->addDay();
    }

    // Get department averages
    $departmentAverages = [];
    foreach ($departments as $department) {
      $avg = MoodLog::whereHas('user.employeeProfile', function ($q) use ($department) {
        $q->where('department_id', $department->id);
      })->avg('mood_level');

      $departmentAverages[] = [
        'department_id' => $department->id,
        'department_name' => $department->name,
        'average' => round($avg, 1) ?: 0,
      ];
    }

    return Inertia::render('Admin/MoodLogs/Index', [
      'moodLogs' => $moodLogs,
      'departments' => $departments,
      'stats' => $stats,
      'trends' => $trends,
      'departmentAverages' => $departmentAverages,
      'filters' => [
        'search' => $request->input('search', ''),
        'mood_level' => $request->input('mood_level', ''),
        'need_support' => $request->input('need_support', null),
        'department' => $request->input('department', ''),
        'start_date' => $request->input('start_date', ''),
        'end_date' => $request->input('end_date', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
      'moodLevels' => [
        1 => 'Very Unhappy',
        2 => 'Unhappy',
        3 => 'Neutral',
        4 => 'Happy',
        5 => 'Very Happy',
      ],
    ]);
  }

  /**
   * Display a listing of mood logs that need support.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function supportNeeded(Request $request)
  {
    $query = MoodLog::where('need_support', true)
      ->with(['user.employeeProfile.department']);

    // Department filter
    if ($request->has('department') && $request->input('department')) {
      $query->whereHas('user.employeeProfile', function ($q) use ($request) {
        $q->where('department_id', $request->input('department'));
      });
    }

    // Date range filter
    if ($request->has('start_date') && $request->input('start_date')) {
      $query->whereDate('created_at', '>=', $request->input('start_date'));
    }

    if ($request->has('end_date') && $request->input('end_date')) {
      $query->whereDate('created_at', '<=', $request->input('end_date'));
    }

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('notes', 'like', "%{$search}%")
          ->orWhereHas('user', function ($userQuery) use ($search) {
            $userQuery->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
          });
      });
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $moodLogs = $query->paginate(10)
      ->withQueryString();

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    return Inertia::render('Admin/MoodLogs/SupportNeeded', [
      'moodLogs' => $moodLogs,
      'departments' => $departments,
      'filters' => [
        'search' => $request->input('search', ''),
        'department' => $request->input('department', ''),
        'start_date' => $request->input('start_date', ''),
        'end_date' => $request->input('end_date', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
      'moodLevels' => [
        1 => 'Very Unhappy',
        2 => 'Unhappy',
        3 => 'Neutral',
        4 => 'Happy',
        5 => 'Very Happy',
      ],
    ]);
  }

  /**
   * Display the specified mood log.
   *
   * @param MoodLog $moodLog
   * @return \Inertia\Response
   */
  public function show(MoodLog $moodLog)
  {
    $moodLog->load(['user.employeeProfile.department']);

    // Get user's mood history
    $moodHistory = MoodLog::where('user_id', $moodLog->user_id)
      ->where('id', '!=', $moodLog->id)
      ->orderBy('created_at', 'desc')
      ->limit(10)
      ->get();

    // Calculate user's mood trends
    $userMoodTrends = [];
    $startDate = Carbon::now()->subDays(29);
    $endDate = Carbon::now();

    $currentDate = $startDate->copy();
    while ($currentDate->lte($endDate)) {
      $dayLog = MoodLog::where('user_id', $moodLog->user_id)
        ->whereDate('created_at', $currentDate)
        ->first();

      $userMoodTrends[] = [
        'date' => $currentDate->format('Y-m-d'),
        'day' => $currentDate->format('d'),
        'month' => $currentDate->format('M'),
        'mood_level' => $dayLog ? $dayLog->mood_level : null,
        'need_support' => $dayLog ? $dayLog->need_support : null,
      ];

      $currentDate->addDay();
    }

    return Inertia::render('Admin/MoodLogs/Show', [
      'moodLog' => $moodLog,
      'moodHistory' => $moodHistory,
      'userMoodTrends' => $userMoodTrends,
      'moodLevels' => [
        1 => 'Very Unhappy',
        2 => 'Unhappy',
        3 => 'Neutral',
        4 => 'Happy',
        5 => 'Very Happy',
      ],
    ]);
  }

  /**
   * Mark that support has been provided for the specified mood log.
   *
   * @param Request $request
   * @param MoodLog $moodLog
   * @return \Illuminate\Http\RedirectResponse
   */
  public function markSupportProvided(Request $request, MoodLog $moodLog)
  {
    $request->validate([
      'support_notes' => 'required|string|max:1000',
    ]);

    // Update the mood log
    $moodLog->update([
      'need_support' => false,
      'notes' => $moodLog->notes . "\n\n--- Support Provided ---\n" . $request->support_notes,
    ]);

    return redirect()->route('admin.mood-logs.show', $moodLog)
      ->with('success', 'Support has been marked as provided.');
  }
}
