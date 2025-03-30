<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'name',
    'description',
    'points',
    'badge_image',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'points' => 'integer',
  ];

  /**
   * Get the user achievements for this achievement.
   */
  public function userAchievements(): HasMany
  {
    return $this->hasMany(UserAchievement::class);
  }

  /**
   * Get the users who have earned this achievement.
   */
  public function users()
  {
    return $this->belongsToMany(User::class, 'user_achievements')
      ->withPivot('earned_at')
      ->withTimestamps();
  }

  /**
   * Scope a query to only include achievements with a minimum number of points.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param int $points
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeMinPoints($query, int $points)
  {
    return $query->where('points', '>=', $points);
  }

  /**
   * Scope a query to only include achievements with a maximum number of points.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param int $points
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeMaxPoints($query, int $points)
  {
    return $query->where('points', '<=', $points);
  }

  /**
   * Get the badge image URL.
   *
   * @return string
   */
  public function getBadgeUrl(): string
  {
    if (!$this->badge_image) {
      return asset('images/default-badge.png');
    }

    return asset('storage/' . $this->badge_image);
  }
}
