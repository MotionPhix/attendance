<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'description',
    'color',
    'days_per_year',
    'requires_approval',
    'requires_attachment',
    'is_active'
  ];

  protected $casts = [
    'days_per_year' => 'integer',
    'requires_approval' => 'boolean',
    'requires_attachment' => 'boolean',
    'is_active' => 'boolean'
  ];

  public function leaveRequests(): HasMany
  {
    return $this->hasMany(LeaveRequest::class);
  }
}
