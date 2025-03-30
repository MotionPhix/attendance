<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'achievement_id',
    'earned_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'earned_at' => 'datetime',
  ];

  /**
   * Get the user that earned the achievement.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the achievement that was earned.
   */
  public function achievement(): BelongsTo
  {
    return $this->belongsTo(Achievement::class);
  }

  /**
   * Scope a query to only include recent achievements.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param int $days
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeRecent($query, int $days = 30)
  {
    return $query->where('earned_at', '>=', now()->subDays($days));
  }

  /**
   * Scope a query to order by most recently earned.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeLatest($query)
  {
    return $query->orderBy('earned_at', 'desc');
  }
}
