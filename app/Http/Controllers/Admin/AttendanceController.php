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
    $query = AttendanceLog::with('user')
      ->when($request->search, function ($query, $search) {
        $query->whereHas('user', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
        });
      })
      ->when($request->date, function ($query, $date) {
        $query->whereDate('check_in_time', $date);
      })
      ->when($request->status, function ($query, $status) {
        switch ($status) {
          case 'late':
            $query->where('late_minutes', '>', 0);
            break;
          case 'early_departure':
            $query->where('early_departure_minutes', '>', 0);
            break;
          case 'present':
            $query->whereDate('check_in_time', today())
              ->whereNotNull('check_in_time');
            break;
          case 'absent':
            // This might need a more complex query depending on your business logic
            $query->whereDate('check_in_time', today())
              ->whereNull('check_in_time');
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
      'filters' => $request->only(['search', 'date', 'status']),
      'stats' => $this->getAttendanceStats(),
    ]);
  }

  public function show(AttendanceLog $attendance)
  {
    return Inertia::render('admin/attendance/Show', [
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

  private function getAttendanceStats(): array
  {
    $today = Carbon::today();
    $totalEmployees = User::count();

    $todayStats = [
      'total_employees' => $totalEmployees,
      'present' => AttendanceLog::whereDate('check_in_time', $today)->count(),
      'late' => AttendanceLog::whereDate('check_in_time', $today)
        ->where('late_minutes', '>', 0)
        ->count(),
      'early_departure' => AttendanceLog::whereDate('check_in_time', $today)
        ->where('early_departure_minutes', '>', 0)
        ->count(),
    ];

    $todayStats['absent'] = $totalEmployees - $todayStats['present'];

    return $todayStats;
  }
}
