<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
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
    $query = LeaveRequest::query()
      ->with(['user.employeeProfile.department']);

    // Status filter
    if ($request->has('status') && $request->input('status')) {
      $query->where('status', $request->input('status'));
    }

    // Department filter
    if ($request->has('department') && $request->input('department')) {
      $query->whereHas('user.employeeProfile', function ($q) use ($request) {
        $q->where('department_id', $request->input('department'));
      });
    }

    // Date range filter
    if ($request->has('start_date') && $request->input('start_date')) {
      $query->where('start_date', '>=', $request->input('start_date'));
    }

    if ($request->has('end_date') && $request->input('end_date')) {
      $query->where('end_date', '<=', $request->input('end_date'));
    }

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->whereHas('user', function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%");
      });
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    if ($sortField === 'employee') {
      $query->join('users', 'leave_requests.user_id', '=', 'users.id')
        ->orderBy('users.name', $sortDirection)
        ->select('leave_requests.*');
    } elseif ($sortField === 'department') {
      $query->join('users', 'leave_requests.user_id', '=', 'users.id')
        ->join('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
        ->join('departments', 'employee_profiles.department_id', '=', 'departments.id')
        ->orderBy('departments.name', $sortDirection)
        ->select('leave_requests.*');
    } else {
      $query->orderBy($sortField, $sortDirection);
    }

    // Pagination
    $leaveRequests = $query->paginate(10)
      ->withQueryString();

    // Get departments for filter
    $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);

    return Inertia::render('Admin/LeaveRequests/Index', [
      'leaveRequests' => $leaveRequests,
      'departments' => $departments,
      'filters' => [
        'search' => $request->input('search', ''),
        'status' => $request->input('status', ''),
        'department' => $request->input('department', ''),
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
   * Display the specified leave request.
   *
   * @param LeaveRequest $leaveRequest
   * @return \Inertia\Response
   */
  public function show(LeaveRequest $leaveRequest)
  {
    $leaveRequest->load(['user.employeeProfile.department']);

    // Get user's leave history
    $leaveHistory = LeaveRequest::where('user_id', $leaveRequest->user_id)
      ->where('id', '!=', $leaveRequest->id)
      ->orderBy('created_at', 'desc')
      ->limit(5)
      ->get();

    return Inertia::render('Admin/LeaveRequests/Show', [
      'leaveRequest' => $leaveRequest,
      'leaveHistory' => $leaveHistory,
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
   * Approve the specified leave request.
   *
   * @param Request $request
   * @param LeaveRequest $leaveRequest
   * @return \Illuminate\Http\RedirectResponse
   */
  public function approve(Request $request, LeaveRequest $leaveRequest)
  {
    // Check if request is already processed
    if ($leaveRequest->status !== 'pending') {
      return redirect()->back()
        ->with('error', 'This leave request has already been processed.');
    }

    $leaveRequest->update([
      'status' => 'approved',
      'approved_by' => Auth::id(),
    ]);

    // Update employee status if leave starts today or is already ongoing
    $today = Carbon::today();
    if ($leaveRequest->start_date->lte($today) && $leaveRequest->end_date->gte($today)) {
      $user = User::find($leaveRequest->user_id);
      if ($user && $user->employeeProfile) {
        $user->employeeProfile->update(['status' => 'on_leave']);
      }
    }

    return redirect()->route('admin.leave-requests.index')
      ->with('success', 'Leave request approved successfully.');
  }

  /**
   * Reject the specified leave request.
   *
   * @param Request $request
   * @param LeaveRequest $leaveRequest
   * @return \Illuminate\Http\RedirectResponse
   */
  public function reject(Request $request, LeaveRequest $leaveRequest)
  {
    $request->validate([
      'rejection_reason' => 'required|string|max:500',
    ]);

    // Check if request is already processed
    if ($leaveRequest->status !== 'pending') {
      return redirect()->back()
        ->with('error', 'This leave request has already been processed.');
    }

    $leaveRequest->update([
      'status' => 'rejected',
      'approved_by' => Auth::id(),
      'rejection_reason' => $request->rejection_reason,
    ]);

    return redirect()->route('admin.leave-requests.index')
      ->with('success', 'Leave request rejected successfully.');
  }
}
