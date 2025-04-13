<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable, HasRoles, InteractsWithMedia;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  /**
   * Append avatar_url to JSON.
   */
  protected $appends = ['avatar_url'];

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('avatar')
      ->singleFile()  // Only keep one file in the collection
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif'])
      ->registerMediaConversions(function (Media $media) {
        $this->addMediaConversion('thumb')
          ->width(100)
          ->height(100)
          ->sharpen(10);

        $this->addMediaConversion('avatar')
          ->width(300)
          ->height(300)
          ->sharpen(10);
      });
  }

  /**
   * Get the employee profile associated with the user.
   */
  public function employeeProfile(): HasOne
  {
    return $this->hasOne(Employee::class);
  }

  /**
   * Get the attendance logs for the user.
   */
  public function attendanceLogs(): HasMany
  {
    return $this->hasMany(AttendanceLog::class);
  }

  /**
   * Get the salary records for the user.
   */
  public function salaryRecords(): HasMany
  {
    return $this->hasMany(SalaryRecord::class);
  }

  /**
   * Get the achievements earned by the user.
   */
  public function achievements(): HasMany
  {
    return $this->hasMany(UserAchievement::class);
  }

  /**
   * Get the goals set by the user.
   */
  public function goals(): HasMany
  {
    return $this->hasMany(Goal::class);
  }

  /**
   * Get the feedback submitted by the user.
   */
  public function feedback(): HasMany
  {
    return $this->hasMany(Feedback::class);
  }

  /**
   * Check if the user has clocked in today.
   *
   * @return bool
   */
  public function hasCheckedInToday(): bool
  {
    return $this->attendanceLogs()
      ->whereDate('check_in_time', now()->toDateString())
      ->exists();
  }

  /**
   * Check if the user is currently clocked in.
   *
   * @return bool
   */
  public function isCurrentlyCheckedIn(): bool
  {
    return $this->attendanceLogs()
      ->whereDate('check_in_time', now()->toDateString())
      ->whereNull('check_out_time')
      ->exists();
  }

  /**
   * Get today's attendance log for the user.
   *
   * @return AttendanceLog|null
   */
  public function getTodayAttendance()
  {
    return $this->attendanceLogs()
      ->whereDate('check_in_time', now()->toDateString())
      ->first();
  }

  /**
   * Get the work schedule applicable for the user today.
   *
   * @return WorkSchedule|null
   */
  public function getTodayWorkSchedule()
  {
    // Get the user's department
    $departmentId = $this->employeeProfile?->department_id;

    if (!$departmentId) {
      return null;
    }

    // Get the day of week (0 = Sunday, 6 = Saturday)
    $dayOfWeek = now()->dayOfWeek;

    // Find a schedule for the user's department that applies to today
    return WorkSchedule::where('department_id', $departmentId)
      ->whereJsonContains('days_of_week', $dayOfWeek)
      ->first();
  }

  /**
   * Check if user is an admin.
   *
   * @return bool
   */
  public function isAdmin(): bool
  {
    return $this->hasRole('admin');
  }

  /**
   * Check if user has admin-level permissions.
   *
   * @return bool
   */
  public function hasAdminAccess(): bool
  {
    return $this->hasAnyRole(['admin', 'hr', 'manager']);
  }

  /**
   * Get the default avatar URL for the user.
   */
  protected function defaultAvatarUrl(): string
  {
    // First try UI Avatars
    $uiAvatarUrl = $this->getUiAvatarUrl();

    // Check if UI Avatars is accessible
    if ($this->isUrlAccessible($uiAvatarUrl)) {
      return $uiAvatarUrl;
    }

    // Fallback to Gravatar
    return $this->getGravatarUrl();
  }

  /**
   * Get UI Avatars URL.
   */
  protected function getUiAvatarUrl(): string
  {
    $name = urlencode($this->name);
    return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&bold=true&format=svg";
  }

  /**
   * Get Gravatar URL.
   */
  protected function getGravatarUrl(): string
  {
    $hash = md5(strtolower(trim($this->email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
  }

  /**
   * Check if a URL is accessible.
   */
  protected function isUrlAccessible(string $url): bool
  {
    // Cache the result for 24 hours to avoid checking on every request
    return Cache::remember("avatar_url_accessible_{$this->id}", 86400, function () use ($url) {
      $headers = @get_headers($url);
      return $headers && strpos($headers[0], '200') !== false;
    });
  }

  /**
   * Get the user's avatar URL.
   */
  protected function avatarUrl(): Attribute
  {
    return Attribute::make(
      get: function () {
        // First try to get the media avatar
        $media = $this->getFirstMedia('avatar');
        if ($media) {
          return $media->original_url;
        }

        // Fallback to default avatar
        return $this->defaultAvatarUrl();
      }
    );
  }
}
