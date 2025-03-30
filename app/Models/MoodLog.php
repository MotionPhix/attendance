<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodLog extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'mood_level',
    'notes',
    'need_support',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'mood_level' => 'integer',
    'need_support' => 'boolean',
  ];

  /**
   * Get the user that owns the mood log.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Scope a query to only include logs that need support.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeNeedSupport($query)
  {
    return $query->where('need_support', true);
  }

  /**
   * Scope a query to only include logs with low mood (1-2).
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeLowMood($query)
  {
    return $query->whereIn('mood_level', [1, 2]);
  }

  /**
   * Scope a query to only include logs with neutral mood (3).
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeNeutralMood($query)
  {
    return $query->where('mood_level', 3);
  }

  /**
   * Scope a query to only include logs with high mood (4-5).
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeHighMood($query)
  {
    return $query->whereIn('mood_level', [4, 5]);
  }

  /**
   * Get the mood description.
   *
   * @return string
   */
  public function getMoodDescription(): string
  {
    $descriptions = [
      1 => 'Very Unhappy',
      2 => 'Unhappy',
      3 => 'Neutral',
      4 => 'Happy',
      5 => 'Very Happy',
    ];

    return $descriptions[$this->mood_level] ?? 'Unknown';
  }

  /**
   * Check if the mood is low (1-2).
   *
   * @return bool
   */
  public function isLowMood(): bool
  {
    return in_array($this->mood_level, [1, 2]);
  }

  /**
   * Check if the mood is neutral (3).
   *
   * @return bool
   */
  public function isNeutralMood(): bool
  {
    return $this->mood_level === 3;
  }

  /**
   * Check if the mood is high (4-5).
   *
   * @return bool
   */
  public function isHighMood(): bool
  {
    return in_array($this->mood_level, [4, 5]);
  }
}
