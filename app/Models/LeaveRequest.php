<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LeaveRequest extends Model implements HasMedia
{
  use HasFactory, InteractsWithMedia;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'start_date',
    'end_date',
    'leave_type_id',
    'total_days',
    'reason',
    'status',
    'reviewed_by',
    'review_notes',
    'submitted_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
    'total_days' => 'integer',
    'submitted_at' => 'datetime',
    'reviewed_at' => 'datetime'
  ];

  /**
   * Get the user that owns the leave request.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function leaveType(): BelongsTo
  {
    return $this->belongsTo(LeaveType::class);
  }

  public function reviewer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('attachments');
  }

  /**
   * Scope a query to only include pending leave requests.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }

  /**
   * Scope a query to only include approved leave requests.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeApproved($query)
  {
    return $query->where('status', 'approved');
  }

  /**
   * Scope a query to only include rejected leave requests.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeRejected($query)
  {
    return $query->where('status', 'rejected');
  }

  /**
   * Check if the leave request is pending.
   *
   * @return bool
   */
  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  /**
   * Check if the leave request is approved.
   *
   * @return bool
   */
  public function isApproved(): bool
  {
    return $this->status === 'approved';
  }

  /**
   * Check if the leave request is rejected.
   *
   * @return bool
   */
  public function isRejected(): bool
  {
    return $this->status === 'rejected';
  }
}
