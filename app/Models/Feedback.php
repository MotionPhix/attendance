<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'type',
    'content',
    'status',
    'response',
    'responded_by',
    'responded_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'responded_at' => 'datetime',
  ];

  /**
   * Get the user that owns the feedback.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the user who responded to the feedback.
   */
  public function responder(): BelongsTo
  {
    return $this->belongsTo(User::class, 'responded_by');
  }

  /**
   * Scope a query to only include pending feedback.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }

  /**
   * Scope a query to only include feedback in review.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeInReview($query)
  {
    return $query->where('status', 'in_review');
  }

  /**
   * Scope a query to only include resolved feedback.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeResolved($query)
  {
    return $query->where('status', 'resolved');
  }

  /**
   * Scope a query to only include closed feedback.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeClosed($query)
  {
    return $query->where('status', 'closed');
  }

  /**
   * Scope a query to only include suggestions.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeSuggestion($query)
  {
    return $query->where('type', 'suggestion');
  }

  /**
   * Scope a query to only include complaints.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeComplaint($query)
  {
    return $query->where('type', 'complaint');
  }

  /**
   * Scope a query to only include praise.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopePraise($query)
  {
    return $query->where('type', 'praise');
  }

  /**
   * Check if the feedback is pending.
   *
   * @return bool
   */
  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  /**
   * Check if the feedback is in review.
   *
   * @return bool
   */
  public function isInReview(): bool
  {
    return $this->status === 'in_review';
  }

  /**
   * Check if the feedback is resolved.
   *
   * @return bool
   */
  public function isResolved(): bool
  {
    return $this->status === 'resolved';
  }

  /**
   * Check if the feedback is closed.
   *
   * @return bool
   */
  public function isClosed(): bool
  {
    return $this->status === 'closed';
  }

  /**
   * Check if the feedback has a response.
   *
   * @return bool
   */
  public function hasResponse(): bool
  {
    return !is_null($this->response);
  }
}
