<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'month',
    'year',
    'base_amount',
    'deductions',
    'bonuses',
    'overtime_pay',
    'net_amount',
    'status',
    'processed_at',
    'paid_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'month' => 'integer',
    'year' => 'integer',
    'base_amount' => 'decimal:2',
    'deductions' => 'decimal:2',
    'bonuses' => 'decimal:2',
    'overtime_pay' => 'decimal:2',
    'net_amount' => 'decimal:2',
    'processed_at' => 'datetime',
    'paid_at' => 'datetime',
  ];

  /**
   * Get the user that owns the salary record.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Scope a query to only include pending salary records.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }

  /**
   * Scope a query to only include processed salary records.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeProcessed($query)
  {
    return $query->where('status', 'processed');
  }

  /**
   * Scope a query to only include paid salary records.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopePaid($query)
  {
    return $query->where('status', 'paid');
  }

  /**
   * Scope a query to filter by month and year.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @param int $month
   * @param int $year
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeForPeriod($query, int $month, int $year)
  {
    return $query->where('month', $month)->where('year', $year);
  }

  /**
   * Check if the salary record is pending.
   *
   * @return bool
   */
  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  /**
   * Check if the salary record is processed.
   *
   * @return bool
   */
  public function isProcessed(): bool
  {
    return $this->status === 'processed';
  }

  /**
   * Check if the salary record is paid.
   *
   * @return bool
   */
  public function isPaid(): bool
  {
    return $this->status === 'paid';
  }

  /**
   * Get the period name (e.g., "January 2023").
   *
   * @return string
   */
  public function getPeriodName(): string
  {
    $monthNames = [
      1 => 'January',
      2 => 'February',
      3 => 'March',
      4 => 'April',
      5 => 'May',
      6 => 'June',
      7 => 'July',
      8 => 'August',
      9 => 'September',
      10 => 'October',
      11 => 'November',
      12 => 'December',
    ];

    return $monthNames[$this->month] . ' ' . $this->year;
  }
}
