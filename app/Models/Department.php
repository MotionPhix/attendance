<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'name',
    'description',
  ];

  /**
   * Get the employees in this department.
   */
  public function employees(): HasMany
  {
    return $this->hasMany(EmployeeProfile::class);
  }

  /**
   * Get the work schedules for this department.
   */
  public function workSchedules(): HasMany
  {
    return $this->hasMany(WorkSchedule::class);
  }

  /**
   * Get the default work schedule for this department.
   *
   * @return WorkSchedule|null
   */
  public function getDefaultSchedule()
  {
    return $this->workSchedules()
      ->where('is_default', true)
      ->first();
  }

  /**
   * Get the count of employees in this department.
   *
   * @return int
   */
  public function getEmployeeCount(): int
  {
    return $this->employees()->count();
  }
}
