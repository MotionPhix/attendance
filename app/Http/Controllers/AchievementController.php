<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AchievementController extends Controller
{
  /**
   * Display a listing of the user's achievements.
   *
   * @return \Inertia\Response
   */
  public function index()
  {
    $user = Auth::user();

    // Get user's earned achievements
    $earnedAchievements = UserAchievement::where('user_id', $user->id)
      ->with('achievement')
      ->orderBy('earned_at', 'desc')
      ->get()
      ->map(function ($userAchievement) {
        return [
          'id' => $userAchievement->achievement->id,
          'name' => $userAchievement->achievement->name,
          'description' => $userAchievement->achievement->description,
          'points' => $userAchievement->achievement->points,
          'badge_url' => $userAchievement->achievement->getBadgeUrl(),
          'earned_at' => $userAchievement->earned_at->format('F d, Y'),
        ];
      });

    // Get all available achievements
    $allAchievements = Achievement::orderBy('points')
      ->get()
      ->map(function ($achievement) use ($earnedAchievements) {
        $earned = $earnedAchievements->contains('id', $achievement->id);

        return [
          'id' => $achievement->id,
          'name' => $achievement->name,
          'description' => $achievement->description,
          'points' => $achievement->points,
          'badge_url' => $achievement->getBadgeUrl(),
          'earned' => $earned,
        ];
      });

    // Group achievements by category (based on name)
    $categories = [
      'attendance' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Attendance');
      })->values(),

      'punctuality' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Punctuality');
      })->values(),

      'overtime' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Extra Mile');
      })->values(),

      'goals' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Goal');
      })->values(),

      'feedback' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Feedback');
      })->values(),

      'mood' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Mood');
      })->values(),

      'milestones' => $allAchievements->filter(function ($achievement) {
        return str_contains($achievement['name'], 'Anniversary');
      })->values(),

      'other' => $allAchievements->filter(function ($achievement) {
        return !str_contains($achievement['name'], 'Attendance') &&
          !str_contains($achievement['name'], 'Punctuality') &&
          !str_contains($achievement['name'], 'Extra Mile') &&
          !str_contains($achievement['name'], 'Goal') &&
          !str_contains($achievement['name'], 'Feedback') &&
          !str_contains($achievement['name'], 'Mood') &&
          !str_contains($achievement['name'], 'Anniversary');
      })->values(),
    ];

    // Calculate total points
    $totalPoints = $earnedAchievements->sum('points');

    // Calculate progress to next level (simplified example)
    $level = floor($totalPoints / 500) + 1;
    $nextLevelPoints = $level * 500;
    $progress = ($totalPoints % 500) / 5; // Percentage to next level

    return Inertia::render('Achievements/Index', [
      'earnedAchievements' => $earnedAchievements,
      'categories' => $categories,
      'stats' => [
        'total_achievements' => $earnedAchievements->count(),
        'total_points' => $totalPoints,
        'level' => $level,
        'next_level_points' => $nextLevelPoints,
        'progress' => $progress,
      ],
    ]);
  }
}
