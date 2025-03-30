<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AchievementController extends Controller
{
  /**
   * Display a listing of the achievements.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $query = Achievement::query();

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where('name', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
    }

    // Points filter
    if ($request->has('min_points') && $request->input('min_points')) {
      $query->where('points', '>=', $request->input('min_points'));
    }

    if ($request->has('max_points') && $request->input('max_points')) {
      $query->where('points', '<=', $request->input('max_points'));
    }

    // Sorting
    $sortField = $request->input('sort_field', 'name');
    $sortDirection = $request->input('sort_direction', 'asc');
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $achievements = $query->paginate(10)
      ->withQueryString();

    // Get achievement categories based on name patterns
    $categories = [
      'attendance' => Achievement::where('name', 'like', '%Attendance%')->count(),
      'punctuality' => Achievement::where('name', 'like', '%Punctuality%')->count(),
      'overtime' => Achievement::where('name', 'like', '%Extra Mile%')->count(),
      'goals' => Achievement::where('name', 'like', '%Goal%')->count(),
      'feedback' => Achievement::where('name', 'like', '%Feedback%')->count(),
      'mood' => Achievement::where('name', 'like', '%Mood%')->count(),
      'milestones' => Achievement::where('name', 'like', '%Anniversary%')->count(),
    ];

    return Inertia::render('Admin/Achievements/Index', [
      'achievements' => $achievements,
      'categories' => $categories,
      'filters' => [
        'search' => $request->input('search', ''),
        'min_points' => $request->input('min_points', ''),
        'max_points' => $request->input('max_points', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
    ]);
  }

  /**
   * Show the form for creating a new achievement.
   *
   * @return \Inertia\Response
   */
  public function create()
  {
    return Inertia::render('Admin/Achievements/Create');
  }

  /**
   * Store a newly created achievement in storage.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(Request $request)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:achievements',
      'description' => 'required|string|max:1000',
      'points' => 'required|integer|min:1|max:1000',
      'badge_image' => 'nullable|image|max:2048',
    ]);

    $achievement = new Achievement();
    $achievement->name = $request->name;
    $achievement->description = $request->description;
    $achievement->points = $request->points;

    if ($request->hasFile('badge_image')) {
      $path = $request->file('badge_image')->store('public/badges');
      $achievement->badge_image = str_replace('public/', '', $path);
    }

    $achievement->save();

    return redirect()->route('admin.achievements.index')
      ->with('success', 'Achievement created successfully.');
  }

  /**
   * Display the specified achievement.
   *
   * @param Achievement $achievement
   * @return \Inertia\Response
   */
  public function show(Achievement $achievement)
  {
    // Get users who have earned this achievement
    $userAchievements = UserAchievement::where('achievement_id', $achievement->id)
      ->with('user.employeeProfile.department')
      ->orderBy('earned_at', 'desc')
      ->paginate(10);

    return Inertia::render('Admin/Achievements/Show', [
      'achievement' => $achievement,
      'userAchievements' => $userAchievements,
      'badgeUrl' => $achievement->getBadgeUrl(),
    ]);
  }

  /**
   * Show the form for editing the specified achievement.
   *
   * @param Achievement $achievement
   * @return \Inertia\Response
   */
  public function edit(Achievement $achievement)
  {
    return Inertia::render('Admin/Achievements/Edit', [
      'achievement' => $achievement,
      'badgeUrl' => $achievement->getBadgeUrl(),
    ]);
  }

  /**
   * Update the specified achievement in storage.
   *
   * @param Request $request
   * @param Achievement $achievement
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request, Achievement $achievement)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:achievements,name,' . $achievement->id,
      'description' => 'required|string|max:1000',
      'points' => 'required|integer|min:1|max:1000',
      'badge_image' => 'nullable|image|max:2048',
    ]);

    $achievement->name = $request->name;
    $achievement->description = $request->description;
    $achievement->points = $request->points;

    if ($request->hasFile('badge_image')) {
      // Delete old image if exists
      if ($achievement->badge_image) {
        Storage::delete('public/' . $achievement->badge_image);
      }

      $path = $request->file('badge_image')->store('public/badges');
      $achievement->badge_image = str_replace('public/', '', $path);
    }

    $achievement->save();

    return redirect()->route('admin.achievements.index')
      ->with('success', 'Achievement updated successfully.');
  }

  /**
   * Remove the specified achievement from storage.
   *
   * @param Achievement $achievement
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(Achievement $achievement)
  {
    // Check if achievement has been earned by any users
    $usersCount = UserAchievement::where('achievement_id', $achievement->id)->count();

    if ($usersCount > 0) {
      return redirect()->back()
        ->with('error', 'Cannot delete achievement that has been earned by users.');
    }

    // Delete badge image if exists
    if ($achievement->badge_image) {
      Storage::delete('public/' . $achievement->badge_image);
    }

    $achievement->delete();

    return redirect()->route('admin.achievements.index')
      ->with('success', 'Achievement deleted successfully.');
  }

  /**
   * Show form to assign achievement to users.
   *
   * @param Achievement $achievement
   * @return \Inertia\Response
   */
  public function assignForm(Achievement $achievement)
  {
    // Get users who haven't earned this achievement yet
    $users = User::whereDoesntHave('userAchievements', function ($query) use ($achievement) {
      $query->where('achievement_id', $achievement->id);
    })
      ->with('employeeProfile.department')
      ->whereHas('employeeProfile', function ($query) {
        $query->where('status', 'active');
      })
      ->get()
      ->map(function ($user) {
        return [
          'id' => $user->id,
          'name' => $user->name,
          'email' => $user->email,
          'department' => $user->employeeProfile->department->name ?? 'Unknown',
          'position' => $user->employeeProfile->position ?? 'Unknown',
        ];
      });

    return Inertia::render('Admin/Achievements/Assign', [
      'achievement' => $achievement,
      'badgeUrl' => $achievement->getBadgeUrl(),
      'users' => $users,
    ]);
  }

  /**
   * Assign achievement to a user.
   *
   * @param Request $request
   * @param Achievement $achievement
   * @param User $user
   * @return \Illuminate\Http\RedirectResponse
   */
  public function assignToUser(Request $request, Achievement $achievement, User $user)
  {
    // Check if user already has this achievement
    $exists = UserAchievement::where('user_id', $user->id)
      ->where('achievement_id', $achievement->id)
      ->exists();

    if ($exists) {
      return redirect()->back()
        ->with('error', 'User already has this achievement.');
    }

    // Create user achievement
    UserAchievement::create([
      'user_id' => $user->id,
      'achievement_id' => $achievement->id,
      'earned_at' => Carbon::now(),
    ]);

    return redirect()->route('admin.achievements.show', $achievement)
      ->with('success', 'Achievement assigned to user successfully.');
  }

  /**
   * Revoke achievement from a user.
   *
   * @param Achievement $achievement
   * @param User $user
   * @return \Illuminate\Http\RedirectResponse
   */
  public function revokeFromUser(Achievement $achievement, User $user)
  {
    // Delete user achievement
    UserAchievement::where('user_id', $user->id)
      ->where('achievement_id', $achievement->id)
      ->delete();

    return redirect()->route('admin.achievements.show', $achievement)
      ->with('success', 'Achievement revoked from user successfully.');
  }
}
