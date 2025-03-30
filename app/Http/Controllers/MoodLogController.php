<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoodLogRequest;
use App\Models\MoodLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    $user = Auth::user();

    // Get date range from request or use current month
    $startDate = $request->input('start_date')
      ? Carbon::parse($request->input('start_date'))
      : Carbon::now()->startOfMonth();

    $endDate = $request->input('end_date')
      ? Carbon::parse($request->input('end_date'))
      : Carbon::now()->endOfMonth();

    // Get mood logs for the date range
    $moodLogs = MoodLog::where('user_id', $user->id)
      ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
      ->orderBy('created_at', 'desc')
      ->get();

    // Calculate mood statistics
    $moodStats = [
      'average' => $moodLogs->avg('mood_level'),
      'highest' => $moodLogs->max('mood_level'),
      'lowest' => $moodLogs->min('mood_level'),
      'count' => $moodLogs->count(),
      'distribution' => [
        1 => $moodLogs->where('mood_level', 1)->count(),
        2 => $moodLogs->where('mood_level', 2)->count(),
        3 => $moodLogs->where('mood_level', 3)->count(),
        4 => $moodLogs->where('mood_level', 4)->count(),
        5 => $moodLogs->where('mood_level', 5)->count(),
      ],
    ];

    // Get mood trends (daily average)
    $moodTrends = [];
    $currentDate = $startDate->copy();

    while ($currentDate->lte($endDate)) {
      $dayLogs = $moodLogs->filter(function ($log) use ($currentDate) {
        return Carbon::parse($log->created_at)->isSameDay($currentDate);
      });

      $moodTrends[] = [
        'date' => $currentDate->format('Y-m-d'),
        'day' => $currentDate->format('d'),
        'month' => $currentDate->format('M'),
        'average' => $dayLogs->count() > 0 ? round($dayLogs->avg('mood_level'), 1) : null,
        'count' => $dayLogs->count(),
      ];

      $currentDate->addDay();
    }

    // Check if user has already logged mood today
    $hasLoggedToday = MoodLog::where('user_id', $user->id)
      ->whereDate('created_at', Carbon::today())
      ->exists();

    return Inertia::render('MoodLogs/Index', [
      'moodLogs' => $moodLogs,
      'moodStats' => $moodStats,
      'moodTrends' => $moodTrends,
      'hasLoggedToday' => $hasLoggedToday,
      'dateRange' => [
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
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
   * Store a newly created mood log in storage.
   *
   * @param MoodLogRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(MoodLogRequest $request)
  {
    $user = Auth::user();

    // Check if user has already logged mood today
    $existingLog = MoodLog::where('user_id', $user->id)
      ->whereDate('created_at', Carbon::today())
      ->first();

    if ($existingLog) {
      // Update existing log
      $existingLog->update([
        'mood_level' => $request->mood_level,
        'notes' => $request->notes,
        'need_support' => $request->need_support,
      ]);

      $message = 'Mood log updated successfully.';
    } else {
      // Create new log
      MoodLog::create([
        'user_id' => $user->id,
        'mood_level' => $request->mood_level,
        'notes' => $request->notes,
        'need_support' => $request->need_support,
      ]);

      $message = 'Mood log created successfully.';
    }

    // If user needs support, notify admin
    if ($request->need_support) {
      // In a real application, this would send a notification to HR or managers
      // For now, we'll just add a note to the success message
      $message .= ' A team member will reach out to provide support.';
    }

    return redirect()->route('mood-logs.index')
      ->with('success', $message);
  }

  /**
   * Display the specified mood log.
   *
   * @param MoodLog $moodLog
   * @return \Inertia\Response
   */
  public function show(MoodLog $moodLog)
  {
    $this->authorize('view', $moodLog);

    return Inertia::render('MoodLogs/Show', [
      'moodLog' => $moodLog,
      'moodLevels' => [
        1 => 'Very Unhappy',
        2 => 'Unhappy',
        3 => 'Neutral',
        4 => 'Happy',
        5 => 'Very Happy',
      ],
    ]);
  }
}
