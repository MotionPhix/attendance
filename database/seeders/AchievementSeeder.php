<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $achievements = [
      // Attendance achievements
      [
        'name' => 'Perfect Attendance - Bronze',
        'description' => 'Achieved perfect attendance for 1 month.',
        'points' => 50,
        'badge_image' => 'badges/attendance-bronze.png',
      ],
      [
        'name' => 'Perfect Attendance - Silver',
        'description' => 'Achieved perfect attendance for 3 consecutive months.',
        'points' => 100,
        'badge_image' => 'badges/attendance-silver.png',
      ],
      [
        'name' => 'Perfect Attendance - Gold',
        'description' => 'Achieved perfect attendance for 6 consecutive months.',
        'points' => 200,
        'badge_image' => 'badges/attendance-gold.png',
      ],
      [
        'name' => 'Perfect Attendance - Platinum',
        'description' => 'Achieved perfect attendance for 1 year.',
        'points' => 500,
        'badge_image' => 'badges/attendance-platinum.png',
      ],

      // Punctuality achievements
      [
        'name' => 'Punctuality Star - Bronze',
        'description' => 'Arrived on time for 20 consecutive working days.',
        'points' => 30,
        'badge_image' => 'badges/punctuality-bronze.png',
      ],
      [
        'name' => 'Punctuality Star - Silver',
        'description' => 'Arrived on time for 50 consecutive working days.',
        'points' => 80,
        'badge_image' => 'badges/punctuality-silver.png',
      ],
      [
        'name' => 'Punctuality Star - Gold',
        'description' => 'Arrived on time for 100 consecutive working days.',
        'points' => 150,
        'badge_image' => 'badges/punctuality-gold.png',
      ],

      // Overtime achievements
      [
        'name' => 'Extra Mile - Bronze',
        'description' => 'Completed 10 hours of overtime in a month.',
        'points' => 40,
        'badge_image' => 'badges/overtime-bronze.png',
      ],
      [
        'name' => 'Extra Mile - Silver',
        'description' => 'Completed 25 hours of overtime in a month.',
        'points' => 90,
        'badge_image' => 'badges/overtime-silver.png',
      ],
      [
        'name' => 'Extra Mile - Gold',
        'description' => 'Completed 50 hours of overtime in a month.',
        'points' => 180,
        'badge_image' => 'badges/overtime-gold.png',
      ],

      // Goal achievements
      [
        'name' => 'Goal Setter',
        'description' => 'Set your first goal.',
        'points' => 10,
        'badge_image' => 'badges/goal-setter.png',
      ],
      [
        'name' => 'Goal Achiever - Bronze',
        'description' => 'Completed 5 goals.',
        'points' => 50,
        'badge_image' => 'badges/goal-bronze.png',
      ],
      [
        'name' => 'Goal Achiever - Silver',
        'description' => 'Completed 15 goals.',
        'points' => 100,
        'badge_image' => 'badges/goal-silver.png',
      ],
      [
        'name' => 'Goal Achiever - Gold',
        'description' => 'Completed 30 goals.',
        'points' => 200,
        'badge_image' => 'badges/goal-gold.png',
      ],

      // Feedback achievements
      [
        'name' => 'Feedback Provider',
        'description' => 'Provided your first feedback.',
        'points' => 10,
        'badge_image' => 'badges/feedback.png',
      ],
      [
        'name' => 'Feedback Enthusiast',
        'description' => 'Provided 10 pieces of feedback.',
        'points' => 50,
        'badge_image' => 'badges/feedback-enthusiast.png',
      ],

      // Mood tracking achievements
      [
        'name' => 'Mood Tracker',
        'description' => 'Tracked your mood for 7 consecutive days.',
        'points' => 20,
        'badge_image' => 'badges/mood-tracker.png',
      ],
      [
        'name' => 'Mood Master',
        'description' => 'Tracked your mood for 30 consecutive days.',
        'points' => 60,
        'badge_image' => 'badges/mood-master.png',
      ],

      // Milestone achievements
      [
        'name' => '1 Year Anniversary',
        'description' => 'Completed 1 year with the company.',
        'points' => 100,
        'badge_image' => 'badges/anniversary-1.png',
      ],
      [
        'name' => '3 Year Anniversary',
        'description' => 'Completed 3 years with the company.',
        'points' => 300,
        'badge_image' => 'badges/anniversary-3.png',
      ],
      [
        'name' => '5 Year Anniversary',
        'description' => 'Completed 5 years with the company.',
        'points' => 500,
        'badge_image' => 'badges/anniversary-5.png',
      ],
      [
        'name' => '10 Year Anniversary',
        'description' => 'Completed 10 years with the company.',
        'points' => 1000,
        'badge_image' => 'badges/anniversary-10.png',
      ],
    ];

    foreach ($achievements as $achievement) {
      Achievement::create($achievement);
    }
  }
}
