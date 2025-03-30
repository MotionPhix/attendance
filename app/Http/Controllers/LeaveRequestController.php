<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequestRequest;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
  /**
   * Display a listing of the leave requests.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $user = Auth::user();

    $query = LeaveRequest::where('user_id', $user->id);

    // Status filter
    if ($request->has('status') && $request->input('status')) {
      $query->where('status', $request->input('status'));
    }

    // Date range filter
    if ($request->has('start_date') && $request->input('start_date')) {
      $query->where('start_date', '>=', $request->input('start_date'));
    }

    if ($request->has('end_date') && $request->input('end_date')) {
      $query->where('end_date', '<=', $request->input('end_date'));
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $leaveRequests = $query->paginate(10)
      ->withQueryString();

    // Get leave balance
    $leaveBalance = $this->getLeaveBalance($user->id);

    return Inertia::render('LeaveRequests/Index', [
      'leaveRequests' => $leaveRequests,
      'leaveBalance' => $leaveBalance,
      'filters' => [
        'status' => $request->input('status', ''),
        'start_date' => $request->input('start_date', ''),
        'end_date' => $request->input('end_date', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
      'statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
      ],
      'leaveTypes' => [
        'annual' => 'Annual Leave',
        'sick' => 'Sick Leave',
        'personal' => 'Personal Leave',
        'unpaid' => 'Unpaid Leave',
      ],
    ]);
  }

  /**
   * Show the form for creating a new leave request.
   *
   * @return \Inertia\Response
   */
  public function create()
  {
    $user = Auth::user();
    $leaveBalance = $this->getLeaveBalance($user->id);

    return Inertia::render('LeaveRequests/Create', [
      'leaveBalance' => $leaveBalance,
      'leaveTypes' => [
        'annual' => 'Annual Leave',
        'sick' => 'Sick Leave',
        'personal' => 'Personal Leave',
        'unpaid' => 'Unpaid Leave',
      ],
    ]);
  }

  /**
   * Store a newly created leave request in storage.
   *
   * @param LeaveRequestRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(LeaveRequestRequest $request)
  {
    $user = Auth::user();

    // Calculate duration in days
    $startDate = Carbon::parse($request->start_date);
    $endDate = Carbon::parse($request->end_date);

    // Count only working days (excluding weekends)
    $durationDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
        return !$date->isWeekend();
      }, $endDate) + 1; // +1 to include the end date

    // Check leave balance for paid leave types
    if ($request->leave_type !== 'unpaid') {
      $leaveBalance = $this->getLeaveBalance($user->id);

      if ($leaveBalance[$request->leave_type] < $durationDays) {
        return redirect()->back()
          ->with('error', "Insufficient {$request->leave_type} leave balance.")
          ->withInput();
      }
    }

    // Create leave request
    LeaveRequest::create([
      'user_id' => $user->id,
      'start_date' => $request->start_date,
      'end_date' => $request->end_date,
      'leave_type' => $request->leave_type,
      'duration_days' => $durationDays,
      'reason' => $request->reason,
      'status' => 'pending',
    ]);

    return redirect()->route('leave-requests.index')
      ->with('success', 'Leave request submitted successfully.');
  }

  /**
   * Display the specified leave request.
   *
   * @param LeaveRequest $leaveRequest
   * @return \Inertia\Response
   */
  public function show(LeaveRequest $leaveRequest)
  {
    $this->authorize('view', $leaveRequest);

    return Inertia::render('LeaveRequests/Show', [
      'leaveRequest' => $leaveRequest,
      'leaveTypes' => [
        'annual' => 'Annual Leave',
        'sick' => 'Sick Leave',
        'personal' => 'Personal Leave',
        'unpaid' => 'Unpaid Leave',
      ],
      'statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
      ],
    ]);
  }

  /**
   * Show the form for editing the specified leave request.
   *
   * @param LeaveRequest $leaveRequest
   * @return \Inertia\Response
   */
  public function edit(LeaveRequest $leaveRequest)
  {
    $this->authorize('update', $leaveRequest);

    $user = Auth::user();
    $leaveBalance = $this->getLeaveBalance($user->id);

    return Inertia::render('LeaveRequests/Edit', [
      'leaveRequest' => $leaveRequest,
      'leaveBalance' => $leaveBalance,
      'leaveTypes' => [
        'annual' => 'Annual Leave',
        'sick' => 'Sick Leave',
        'personal' => 'Personal Leave',
        'unpaid' => 'Unpaid Leave',
      ],
    ]);
  }

  /**
   * Update the specified leave request in storage.
   *
   * @param LeaveRequestRequest $request
   * @param LeaveRequest $leaveRequest
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(LeaveRequestRequest $request, LeaveRequest $leaveRequest)
  {
    $this->authorize('update', $leaveRequest);

    // Only allow updates if the request is still pending
    if ($leaveRequest->status !== 'pending') {
      return redirect()->back()
        ->with('error', 'Cannot update a leave request that has already been processed.');
    }

    $user = Auth::user();

    // Calculate duration in days
    $startDate = Carbon::parse($request->start_date);
    $endDate = Carbon::parse($request->end_date);

    // Count only working days (excluding weekends)
    $durationDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
        return !$date->isWeekend();
      }, $endDate) + 1; // +1 to include the end date

    // Check leave balance for paid leave types
    if ($request->leave_type !== 'unpaid' && $request->leave_type !== $leaveRequest->leave_type) {
      $leaveBalance = $this->getLeaveBalance($user->id);

      if ($leaveBalance[$request->leave_type] < $durationDays) {
        return redirect()->back()
          ->with('error', "Insufficient {$request->leave_type} leave balance.")
          ->withInput();
      }
    }

    // Update leave request
    $leaveRequest->update([
      'start_date' => $request->start_date,
      'end_date' => $request->end_date,
      'leave_type' => $request->leave_type,
      'duration_days' => $durationDays,
      'reason' => $request->reason,
    ]);

    return redirect()->route('leave-requests.index')
      ->with('success', 'Leave request updated successfully.');
  }

  /**
   * Remove the specified leave request from storage.
   *
   * @param LeaveRequest $leaveRequest
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(LeaveRequest $leaveRequest)
  {
    $this->authorize('delete', $leaveRequest);

    // Only allow cancellation if the request is still pending
    if ($leaveRequest->status !== 'pending') {
      return redirect()->back()
        ->with('error', 'Cannot cancel a leave request that has already been processed.');
    }

    // Update status to cancelled instead of deleting
    $leaveRequest->update([
      'status' => 'cancelled',
    ]);

    return redirect()->route('leave-requests.index')
      ->with('success', 'Leave request cancelled successfully.');
  }

  /**
   * Get leave balance for a user.
   *
   * @param int $userId
   * @return array<string, int>
   */
  private function getLeaveBalance(int $userId): array
  {
    // In a real application, this would be fetched from a leave balance table
    // For now, we'll use a simplified approach with fixed annual allocations

    $user = Auth::user();
    $employeeProfile = $user->employeeProfile;

    if (!$employeeProfile) {
      return [
        'annual' => 0,
        'sick' => 0,
        'personal' => 0,
      ];
    }

    // Calculate years of service
    $hireDate = Carbon::parse($employeeProfile->hire_date);
    $yearsOfService = $hireDate->diffInYears(Carbon::now());

    // Base allocations
    $annualLeaveAllocation = 20; // 20 days per year
    $sickLeaveAllocation = 10;   // 10 days per year
    $personalLeaveAllocation = 3; // 3 days per year

    // Adjust annual leave based on years of service
    if ($yearsOfService >= 5) {
      $annualLeaveAllocation += 5; // +5 days for 5+ years of service
    } elseif ($yearsOfService >= 3) {
      $annualLeaveAllocation += 2; // +2 days for 3+ years of service
    }

    // Get used leave for the current year
    $currentYear = Carbon::now()->year;
    $startOfYear = Carbon::createFromDate($currentYear, 1, 1)->startOfDay();
    $endOfYear = Carbon::createFromDate($currentYear, 12, 31)->endOfDay();

    $usedLeave = LeaveRequest::where('user_id', $userId)
      ->whereBetween('start_date', [$startOfYear, $endOfYear])
      ->whereIn('status', ['approved', 'pending'])
      ->selectRaw('leave_type, SUM(duration_days) as total_days')
      ->groupBy('leave_type')
      ->pluck('total_days', 'leave_type')
      ->toArray();

    // Calculate remaining balance
    return [
      'annual' => $annualLeaveAllocation - ($usedLeave['annual'] ?? 0),
      'sick' => $sickLeaveAllocation - ($usedLeave['sick'] ?? 0),
      'personal' => $personalLeaveAllocation - ($usedLeave['personal'] ?? 0),
    ];
  }
}
