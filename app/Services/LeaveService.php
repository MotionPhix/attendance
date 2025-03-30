<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LeaveService
{
  /**
   * Get leave summary for a user within a date range.
   *
   * @param int $userId
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return array<string, mixed>
   */
  public function getLeaveSummary(int $userId, Carbon $startDate, Carbon $endDate): array
  {
    // Get leave requests for the period
    $leaveRequests = LeaveRequest::where('user_id', $userId)
      ->where('status', 'approved')
      ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('start_date', [$startDate, $endDate])
          ->orWhereBetween('end_date', [$startDate, $endDate])
          ->orWhere(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<', $startDate)
              ->where('end_date', '>', $endDate);
          });
      })
      ->get();

    // Calculate leave days by type
    $paidLeaveDays = 0;
    $unpaidLeaveDays = 0;
    $sickLeaveDays = 0;
    $vacationLeaveDays = 0;
    $otherLeaveDays = 0;

    foreach ($leaveRequests as $leave) {
      // Calculate days that fall within the specified period
      $leaveStart = max(Carbon::parse($leave->start_date), $startDate);
      $leaveEnd = min(Carbon::parse($leave->end_date), $endDate);

      $leaveDays = $leaveStart->diffInDaysFiltered(function (Carbon $date) {
          return !$date->isWeekend(); // Skip weekends
        }, $leaveEnd) + 1; // +1 to include the end date

      // Categorize by leave type
      switch ($leave->leave_type) {
        case 'unpaid':
          $unpaidLeaveDays += $leaveDays;
          break;
        case 'sick':
          $sickLeaveDays += $leaveDays;
          $paidLeaveDays += $leaveDays; // Sick leave is typically paid
          break;
        case 'vacation':
          $vacationLeaveDays += $leaveDays;
          $paidLeaveDays += $leaveDays; // Vacation is typically paid
          break;
        default:
          $otherLeaveDays += $leaveDays;
          // Assume other types are paid unless explicitly marked as unpaid
          if ($leave->leave_type !== 'unpaid') {
            $paidLeaveDays += $leaveDays;
          }
          break;
      }
    }

    return [
      'period' => [
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
      ],
      'total_leave_days' => $paidLeaveDays + $unpaidLeaveDays,
      'paid_leave_days' => $paidLeaveDays,
      'unpaid_leave_days' => $unpaidLeaveDays,
      'sick_leave_days' => $sickLeaveDays,
      'vacation_leave_days' => $vacationLeaveDays,
      'other_leave_days' => $otherLeaveDays,
      'leave_requests' => $leaveRequests->map(function ($leave) {
        return [
          'id' => $leave->id,
          'start_date' => $leave->start_date->toDateString(),
          'end_date' => $leave->end_date->toDateString(),
          'leave_type' => $leave->leave_type,
          'duration_days' => $leave->duration_days,
          'reason' => $leave->reason,
        ];
      }),
    ];
  }

  /**
   * Apply for leave.
   *
   * @param User $user
   * @param array $data
   * @return LeaveRequest
   */
  public function applyForLeave(User $user, array $data): LeaveRequest
  {
    $startDate = Carbon::parse($data['start_date']);
    $endDate = Carbon::parse($data['end_date']);

    // Calculate duration in days (excluding weekends if specified)
    $durationDays = $startDate->diffInDaysFiltered(function (Carbon $date) use ($data) {
        // Skip weekends if exclude_weekends is true
        return !($data['exclude_weekends'] ?? false) || !$date->isWeekend();
      }, $endDate) + 1; // +1 to include the end date

    // Check for overlapping leave requests
    $overlappingLeaves = LeaveRequest::where('user_id', $user->id)
      ->where('status', 'approved')
      ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('start_date', [$startDate, $endDate])
          ->orWhereBetween('end_date', [$startDate, $endDate])
          ->orWhere(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<', $startDate)
              ->where('end_date', '>', $endDate);
          });
      })
      ->exists();

    if ($overlappingLeaves) {
      throw new \Exception('You already have approved leave during this period.');
    }

    // Create leave request
    return LeaveRequest::create([
      'user_id' => $user->id,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'leave_type' => $data['leave_type'],
      'duration_days' => $durationDays,
      'reason' => $data['reason'],
      'status' => 'pending',
    ]);
  }

  /**
   * Approve a leave request.
   *
   * @param LeaveRequest $leaveRequest
   * @param User $approver
   * @param string|null $comments
   * @return LeaveRequest
   */
  public function approveLeave(LeaveRequest $leaveRequest, User $approver, ?string $comments = null): LeaveRequest
  {
    if (!$approver->hasPermissionTo('approve leaves')) {
      throw new \Exception('You do not have permission to approve leave requests.');
    }

    if ($leaveRequest->status !== 'pending') {
      throw new \Exception('Only pending leave requests can be approved.');
    }

    $leaveRequest->update([
      'status' => 'approved',
      'approved_by' => $approver->id,
      'approval_comments' => $comments,
      'approved_at' => now(),
    ]);

    // Notify the user that their leave has been approved
    // $leaveRequest->user->notify(new LeaveApproved($leaveRequest));

    return $leaveRequest;
  }

  /**
   * Reject a leave request.
   *
   * @param LeaveRequest $leaveRequest
   * @param User $approver
   * @param string $rejectionReason
   * @return LeaveRequest
   */
  public function rejectLeave(LeaveRequest $leaveRequest, User $approver, string $rejectionReason): LeaveRequest
  {
    if (!$approver->hasPermissionTo('approve leaves')) {
      throw new \Exception('You do not have permission to reject leave requests.');
    }

    if ($leaveRequest->status !== 'pending') {
      throw new \Exception('Only pending leave requests can be rejected.');
    }

    $leaveRequest->update([
      'status' => 'rejected',
      'approved_by' => $approver->id,
      'rejection_reason' => $rejectionReason,
      'approved_at' => now(),
    ]);

    // Notify the user that their leave has been rejected
    // $leaveRequest->user->notify(new LeaveRejected($leaveRequest));

    return $leaveRequest;
  }

  /**
   * Cancel a leave request.
   *
   * @param LeaveRequest $leaveRequest
   * @param User $user
   * @return LeaveRequest
   */
  public function cancelLeave(LeaveRequest $leaveRequest, User $user): LeaveRequest
  {
    // Check if the user is the owner of the leave request or has admin permissions
    if ($leaveRequest->user_id !== $user->id && !$user->hasPermissionTo('manage leaves')) {
      throw new \Exception('You do not have permission to cancel this leave request.');
    }

    // Only pending or approved leaves that haven't started yet can be cancelled
    if ($leaveRequest->status !== 'pending' &&
      !($leaveRequest->status === 'approved' && $leaveRequest->start_date->isFuture())) {
      throw new \Exception('This leave request cannot be cancelled.');
    }

    $leaveRequest->update([
      'status' => 'cancelled',
      'cancellation_reason' => 'Cancelled by ' . ($leaveRequest->user_id === $user->id ? 'employee' : 'administrator'),
      'cancelled_at' => now(),
    ]);

    return $leaveRequest;
  }

  /**
   * Get leave balance for a user.
   *
   * @param User $user
   * @param int $year
   * @return array<string, mixed>
   */
  public function getLeaveBalance(User $user, int $year = null): array
  {
    $year = $year ?? now()->year;

    // Get leave policy from settings
    $annualLeaveEntitlement = setting('annual_leave_days', 20);
    $sickLeaveEntitlement = setting('sick_leave_days', 10);
    $otherLeaveEntitlement = setting('other_leave_days', 5);

    // Get used leave days for the year
    $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
    $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear();

    $leaveSummary = $this->getLeaveSummary($user->id, $startDate, $endDate);

    // Calculate remaining leave days
    $remainingAnnualLeave = $annualLeaveEntitlement - $leaveSummary['vacation_leave_days'];
    $remainingSickLeave = $sickLeaveEntitlement - $leaveSummary['sick_leave_days'];
    $remainingOtherLeave = $otherLeaveEntitlement - $leaveSummary['other_leave_days'];

    return [
      'year' => $year,
      'entitlement' => [
        'annual' => $annualLeaveEntitlement,
        'sick' => $sickLeaveEntitlement,
        'other' => $otherLeaveEntitlement,
      ],
      'used' => [
        'annual' => $leaveSummary['vacation_leave_days'],
        'sick' => $leaveSummary['sick_leave_days'],
        'other' => $leaveSummary['other_leave_days'],
        'unpaid' => $leaveSummary['unpaid_leave_days'],
      ],
      'remaining' => [
        'annual' => max(0, $remainingAnnualLeave),
        'sick' => max(0, $remainingSickLeave),
        'other' => max(0, $remainingOtherLeave),
      ],
      'pending_requests' => LeaveRequest::where('user_id', $user->id)
        ->where('status', 'pending')
        ->count(),
    ];
  }

  /**
   * Get department leave report.
   *
   * @param int $departmentId
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return array<string, mixed>
   */
  public function getDepartmentLeaveReport(int $departmentId, Carbon $startDate, Carbon $endDate): array
  {
    $users = User::whereHas('employeeProfile', function ($query) use ($departmentId) {
      $query->where('department_id', $departmentId);
    })->get();

    $report = [
      'department_id' => $departmentId,
      'period' => [
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
      ],
      'summary' => [
        'total_employees' => $users->count(),
        'total_leave_days' => 0,
        'paid_leave_days' => 0,
        'unpaid_leave_days' => 0,
        'sick_leave_days' => 0,
        'vacation_leave_days' => 0,
      ],
      'employees' => [],
    ];

    foreach ($users as $user) {
      $leaveSummary = $this->getLeaveSummary($user->id, $startDate, $endDate);

      $report['employees'][] = [
        'user_id' => $user->id,
        'name' => $user->name,
        'total_leave_days' => $leaveSummary['total_leave_days'],
        'paid_leave_days' => $leaveSummary['paid_leave_days'],
        'unpaid_leave_days' => $leaveSummary['unpaid_leave_days'],
        'sick_leave_days' => $leaveSummary['sick_leave_days'],
        'vacation_leave_days' => $leaveSummary['vacation_leave_days'],
      ];

      // Update summary totals
      $report['summary']['total_leave_days'] += $leaveSummary['total_leave_days'];
      $report['summary']['paid_leave_days'] += $leaveSummary['paid_leave_days'];
      $report['summary']['unpaid_leave_days'] += $leaveSummary['unpaid_leave_days'];
      $report['summary']['sick_leave_days'] += $leaveSummary['sick_leave_days'];
      $report['summary']['vacation_leave_days'] += $leaveSummary['vacation_leave_days'];
    }

    return $report;
  }
}
