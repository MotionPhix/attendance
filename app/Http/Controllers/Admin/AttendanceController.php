<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AttendanceController extends Controller
{
  public function index(Request $request)
  {
    // Validate the request parameters
    $validated = $request->validate([
      'search' => ['nullable', 'string', 'max:100'],
      'date' => ['nullable', 'date_format:Y-m-d'],
      'status' => ['nullable', 'string', 'in:present,absent,late,early_departure'],
    ]);

    // Get the date to filter by (default to today if not provided)
    $date = $validated['date'] ?? now()->format('Y-m-d');

    $query = AttendanceLog::with('user')
      ->when($validated['search'] ?? null, function ($query, $search) {
        $query->whereHas('user', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
        });
      })
      ->when($date, function ($query, $date) {
        $query->whereDate('check_in_time', $date);
      })
      ->when($validated['status'] ?? null, function ($query, $status) {
        switch ($status) {
          case 'late':
            $query->where('late_minutes', '>', 0);
            break;
          case 'early_departure':
            $query->where('early_departure_minutes', '>', 0);
            break;
          case 'present':
            $query->whereNotNull('check_in_time')
              ->where('late_minutes', 0)
              ->where('early_departure_minutes', 0);
            break;
          case 'absent':
            // Get all users who don't have an attendance record for this date
            $query->whereNull('check_in_time')
              ->orWhereNotExists(function ($q) use ($date) {
                $q->select('id')
                  ->from('attendance_logs')
                  ->whereColumn('attendance_logs.user_id', 'users.id')
                  ->whereDate('check_in_time', $date);
              });
            break;
        }
      })
      ->latest('check_in_time');

    return Inertia::render('admin/attendance/Index', [
      'attendance' => $query->paginate(10)->through(function ($record) {
        return [
          'id' => $record->id,
          'user' => [
            'id' => $record->user->id,
            'name' => $record->user->name,
            'email' => $record->user->email,
          ],
          'date' => $record->check_in_time?->format('Y-m-d'),
          'check_in_time' => $record->check_in_time?->format('H:i:s'),
          'check_out_time' => $record->check_out_time?->format('H:i:s'),
          'late_minutes' => $record->late_minutes,
          'early_departure_minutes' => $record->early_departure_minutes,
          'status' => $this->getAttendanceStatus($record),
          'notes' => $record->notes,
        ];
      }),
      'filters' => [
        'search' => $validated['search'] ?? '',
        'date' => $date,
        'status' => $validated['status'] ?? null,
      ],
      'stats' => $this->getAttendanceStats($date),
    ]);
  }

  public function show(AttendanceLog $attendance)
  {
    $user = $attendance->user;

    return Inertia::render('admin/attendance/Show', [
      'attendance' => [
        'id' => $attendance->id,
        'user' => [
          'id' => $attendance->user->id,
          'name' => $attendance->user->name,
          'email' => $attendance->user->email,
          'avatar_url' => $user->avatar_url, // This will use the model's avatarUrl attribute
          // Include the media information if it exists
          'media' => $user->getFirstMedia('avatar') ? [
              $user->getFirstMedia('avatar')->toArray() + [
                'original_url' => $user->getFirstMedia('avatar')->getUrl(),
                'preview_url' => $user->getFirstMedia('avatar')->getUrl('thumb'),
              ]
          ] : [],
        ],
        'date' => $attendance->check_in_time?->format('Y-m-d'),
        'check_in_time' => $attendance->check_in_time?->format('H:i:s'),
        'check_out_time' => $attendance->check_out_time?->format('H:i:s'),
        'late_minutes' => $attendance->late_minutes,
        'early_departure_minutes' => $attendance->early_departure_minutes,
        'notes' => $attendance->notes,
        'status' => $this->getAttendanceStatus($attendance),
      ],
    ]);
  }

  public function edit(AttendanceLog $attendance)
  {
    return Inertia::render('admin/attendance/Edit', [
      'attendance' => [
        'id' => $attendance->id,
        'user' => [
          'id' => $attendance->user->id,
          'name' => $attendance->user->name,
          'email' => $attendance->user->email,
        ],
        'date' => $attendance->check_in_time?->format('Y-m-d'),
        'check_in_time' => $attendance->check_in_time?->format('H:i:s'),
        'check_out_time' => $attendance->check_out_time?->format('H:i:s'),
        'late_minutes' => $attendance->late_minutes,
        'early_departure_minutes' => $attendance->early_departure_minutes,
        'notes' => $attendance->notes,
      ],
    ]);
  }

  public function update(Request $request, AttendanceLog $attendance)
  {
    $validated = $request->validate([
      'check_in_time' => ['required', 'date_format:H:i:s'],
      'check_out_time' => ['nullable', 'date_format:H:i:s', 'after:check_in_time'],
      'notes' => ['nullable', 'string', 'max:500'],
    ]);

    $date = Carbon::parse($attendance->check_in_time)->format('Y-m-d');

    // Create Carbon instances for calculations
    $checkInTime = Carbon::parse($date . ' ' . $validated['check_in_time']);
    $checkOutTime = $validated['check_out_time']
      ? Carbon::parse($date . ' ' . $validated['check_out_time'])
      : null;

    // Get the user's scheduled times
    $user = $attendance->user;
    $schedule = $user->workSchedule ?? $user->department?->workSchedule;

    if ($schedule) {
      $scheduledStartTime = Carbon::parse($date . ' ' . $schedule->start_time);
      $scheduledEndTime = Carbon::parse($date . ' ' . $schedule->end_time);

      // Calculate late and early departure minutes
      $lateMinutes = AttendanceLog::calculateLateMinutes(
        $checkInTime,
        $scheduledStartTime,
        $schedule->grace_period_minutes ?? 5
      );

      $earlyDepartureMinutes = $checkOutTime
        ? AttendanceLog::calculateEarlyDepartureMinutes($checkOutTime, $scheduledEndTime)
        : 0;
    }

    $attendance->update([
      'check_in_time' => $checkInTime,
      'check_out_time' => $checkOutTime,
      'late_minutes' => $lateMinutes ?? 0,
      'early_departure_minutes' => $earlyDepartureMinutes ?? 0,
      'notes' => $validated['notes'],
    ]);

    return redirect()->route('admin.attendance.index')
      ->with('success', 'Attendance record updated successfully.');
  }

  public function destroy(AttendanceLog $attendance)
  {
    $attendance->delete();

    return redirect()->route('admin.attendance.index')
      ->with('success', 'Attendance record deleted successfully.');
  }

  private function getAttendanceStatus(AttendanceLog $attendance): string
  {
    if (!$attendance->check_in_time) {
      return 'absent';
    }

    if ($attendance->late_minutes > 0) {
      return 'late';
    }

    if ($attendance->early_departure_minutes > 0) {
      return 'early_departure';
    }

    return 'present';
  }

  // Update the stats method to accept a date parameter
  private function getAttendanceStats($date = null): array
  {
    $date = $date ? Carbon::parse($date) : Carbon::today();
    $totalEmployees = User::count();

    $stats = [
      'total_employees' => $totalEmployees,
      'present' => AttendanceLog::whereDate('check_in_time', $date)
        ->whereNotNull('check_in_time')
        ->where('late_minutes', 0)
        ->where('early_departure_minutes', 0)
        ->count(),
      'late' => AttendanceLog::whereDate('check_in_time', $date)
        ->where('late_minutes', '>', 0)
        ->count(),
      'early_departure' => AttendanceLog::whereDate('check_in_time', $date)
        ->where('early_departure_minutes', '>', 0)
        ->count(),
    ];

    // Calculate absences
    $stats['absent'] = $totalEmployees - (
      AttendanceLog::whereDate('check_in_time', $date)
        ->whereNotNull('check_in_time')
        ->distinct('user_id')
        ->count()
      );

    return $stats;
  }

}
