<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GoalController extends Controller
{
  /**
   * Display a listing of the goals.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $user = Auth::user();

    $query = Goal::where('user_id', $user->id);

    // Status filter
    if ($request->has('status') && $request->input('status')) {
      $query->where('status', $request->input('status'));
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $goals = $query->paginate(10)
      ->withQueryString();

    // Get goal statistics
    $stats = [
      'total' => Goal::where('user_id', $user->id)->count(),
      'completed' => Goal::where('user_id', $user->id)->where('status', 'completed')->count(),
      'in_progress' => Goal::where('user_id', $user->id)->where('status', 'in_progress')->count(),
      'not_started' => Goal::where('user_id', $user->id)->where('status', 'not_started')->count(),
    ];

    return Inertia::render('Goals/Index', [
      'goals' => $goals,
      'stats' => $stats,
      'filters' => [
        'status' => $request->input('status', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
      'statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
      ],
    ]);
  }

  /**
   * Show the form for creating a new goal.
   *
   * @return \Inertia\Response
   */
  public function create()
  {
    return Inertia::render('Goals/Create', [
      'statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
      ],
    ]);
  }

  /**
   * Store a newly created goal in storage.
   *
   * @param GoalRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(GoalRequest $request)
  {
    $user = Auth::user();

    Goal::create([
      'user_id' => $user->id,
      'title' => $request->title,
      'description' => $request->description,
      'target_date' => $request->target_date,
      'status' => $request->status,
      'progress' => $request->progress ?? 0,
    ]);

    return redirect()->route('goals.index')
      ->with('success', 'Goal created successfully.');
  }

  /**
   * Display the specified goal.
   *
   * @param Goal $goal
   * @return \Inertia\Response
   */
  public function show(Goal $goal)
  {
    $this->authorize('view', $goal);

    return Inertia::render('Goals/Show', [
      'goal' => $goal,
      'statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
      ],
    ]);
  }

  /**
   * Show the form for editing the specified goal.
   *
   * @param Goal $goal
   * @return \Inertia\Response
   */
  public function edit(Goal $goal)
  {
    $this->authorize('update', $goal);

    return Inertia::render('Goals/Edit', [
      'goal' => $goal,
      'statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
      ],
    ]);
  }

  /**
   * Update the specified goal in storage.
   *
   * @param GoalRequest $request
   * @param Goal $goal
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(GoalRequest $request, Goal $goal)
  {
    $this->authorize('update', $goal);

    $goal->update([
      'title' => $request->title,
      'description' => $request->description,
      'target_date' => $request->target_date,
      'status' => $request->status,
      'progress' => $request->progress,
    ]);

    // If status is completed, set progress to 100%
    if ($request->status === 'completed' && $request->progress < 100) {
      $goal->update(['progress' => 100]);
    }

    return redirect()->route('goals.index')
      ->with('success', 'Goal updated successfully.');
  }

  /**
   * Remove the specified goal from storage.
   *
   * @param Goal $goal
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(Goal $goal)
  {
    $this->authorize('delete', $goal);

    $goal->delete();

    return redirect()->route('goals.index')
      ->with('success', 'Goal deleted successfully.');
  }

  /**
   * Update goal progress.
   *
   * @param Request $request
   * @param Goal $goal
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateProgress(Request $request, Goal $goal)
  {
    $this->authorize('update', $goal);

    $request->validate([
      'progress' => 'required|integer|min:0|max:100',
    ]);

    $goal->update([
      'progress' => $request->progress,
      'status' => $request->progress == 0 ? 'not_started' : ($request->progress == 100 ? 'completed' : 'in_progress'),
    ]);

    return redirect()->back()
      ->with('success', 'Goal progress updated successfully.');
  }
}
