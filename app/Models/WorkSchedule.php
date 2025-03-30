<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSchedule extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'department_id',
    'name',
    'start_time',
    'end_time',
    'break_duration',
    'is_default',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'start_time' => 'datetime:H:i',
    'end_time' => 'datetime:H:i',
    'break_duration' => 'integer',
    'is_default' => 'boolean',
  ];

  /**
   * Get the department that owns the work schedule.
   */
  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  /**
   * Scope a query to only include default schedules.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeDefault($query)
  {
    return $query->where('is_default', true);
  }

  /**
   * Get the formatted start time.
   *
   * @return string
   */
  public function getFormattedStartTimeAttribute(): string
  {
    return $this->start_time->format('h:i A');
  }

  /**
   * Get the formatted end time.
   *
   * @return string
   */
  public function getFormattedEndTimeAttribute(): string
  {
    return $this->end_time->format('h:i A');
  }

  /**
   * Get the total work hours (excluding break).
   *
   * @return float
   */
  public function getTotalWorkHoursAttribute(): float
  {
    $startTime = $this->start_time;
    $endTime = $this->end_time;

    // Calculate total minutes
    $totalMinutes = $endTime->diffInMinutes($startTime);

    // Subtract break duration
    $workMinutes = $totalMinutes - $this->break_duration;

    // Convert to hours
    return round($workMinutes / 60, 2);
  }

  /**
   * Check if a given time is within the work schedule.
   *
   * @param \Carbon\Carbon $time
   * @return bool
   */
  public function isWithinWorkHours($time): bool
  {
    $startTime = $this->start_time->copy()->setDateFrom($time);
    $endTime = $this->end_time->copy()->setDateFrom($time);

    return $time->between($startTime, $endTime);
  }
}
