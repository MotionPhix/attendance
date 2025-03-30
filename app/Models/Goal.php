<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'title',
    'description',
    'target_date',
    'status',
    'progress',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'target_date' => 'date',
    'progress' => 'integer',
  ];

  /**
   * Get the user that owns the goal.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Scope a query to only include goals with "not started" status.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeNotStarted($query)
  {
    return $query->where('status', 'not_started');
  }

  /**
   * Scope a query to only include goals with "in progress" status.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeInProgress($query)
  {
    return $query->where('status', 'in_progress');
  }

  /**
   * Scope a query to only include goals with "completed" status.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeCompleted($query)
  {
    return $query->where('status', 'completed');
  }

  /**
   * Scope a query to only include goals that are due soon (within 7 days).
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeDueSoon($query)
  {
    $today = now();
    $sevenDaysLater = now()->addDays(7);

    return $query->whereDate('target_date', '>=', $today)
      ->whereDate('target_date', '<=', $sevenDaysLater)
      ->where('status', '!=', 'completed');
  }

  /**
   * Scope a query to only include overdue goals.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeOverdue($query)
  {
    return $query->whereDate('target_date', '<', now())
      ->where('status', '!=', 'completed');
  }

  /**
   * Check if the goal is not started.
   *
   * @return bool
   */
  public function isNotStarted(): bool
  {
    return $this->status === 'not_started';
  }

  /**
   * Check if the goal is in progress.
   *
   * @return bool
   */
  public function isInProgress(): bool
  {
    return $this->status === 'in_progress';
  }

  /**
   * Check if the goal is completed.
   *
   * @return bool
   */
  public function isCompleted(): bool
  {
    return $this->status === 'completed';
  }

  /**
   * Check if the goal is due soon (within 7 days).
   *
   * @return bool
   */
  public function isDueSoon(): bool
  {
    if ($this->isCompleted()) {
      return false;
    }

    $today = now();
    $sevenDaysLater = now()->addDays(7);

    return $this->target_date->greaterThanOrEqualTo($today) &&
      $this->target_date->lessThanOrEqualTo($sevenDaysLater);
  }

  /**
   * Check if the goal is overdue.
   *
   * @return bool
   */
  public function isOverdue(): bool
  {
    if ($this->isCompleted()) {
      return false;
    }

    return $this->target_date->lessThan(now());
  }
}
