<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
  public function index(Request $request)
  {
    $query = LeaveRequest::query()
      ->with([
        'user:id,name',
        'user.employeeProfile:id,user_id,department_id,position',
        'user.employeeProfile.department:id,name',
        'leaveType:id,name,color',
        'attachments:id,leave_request_id,name,path'
      ]);

    // Status filter
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Date range filter
    if ($request->filled('from')) {
      $query->where('start_date', '>=', $request->from);
    }

    if ($request->filled('to')) {
      $query->where('end_date', '<=', $request->to);
    }

    $requests = $query->latest('submitted_at')
      ->paginate(10)
      ->through(fn ($request) => [
        'id' => $request->id,
        'user' => [
          'id' => $request->user->id,
          'name' => $request->user->name,
          'department' => $request->user->employeeProfile->department->name,
          'position' => $request->user->employeeProfile->position,
          'avatar' => $request->user->profile_photo_url
        ],
        'leave_type' => [
          'id' => $request->leaveType->id,
          'name' => $request->leaveType->name,
          'color' => $request->leaveType->color
        ],
        'start_date' => $request->start_date->format('Y-m-d'),
        'end_date' => $request->end_date->format('Y-m-d'),
        'total_days' => $request->total_days,
        'reason' => $request->reason,
        'status' => $request->status,
        'submitted_at' => $request->submitted_at->format('Y-m-d H:i:s'),
        'reviewed_by' => $request->reviewer ? [
          'id' => $request->reviewer->id,
          'name' => $request->reviewer->name
        ] : null,
        'review_notes' => $request->review_notes,
        'attachments' => $request->attachments->map(fn ($attachment) => [
          'id' => $attachment->id,
          'name' => $attachment->name,
          'url' => $attachment->path
        ])
      ]);

    return Inertia::render('admin/leave-requests/Index', [
      'requests' => $requests,
      'filters' => [
        'status' => $request->status,
        'date_range' => [
          'from' => $request->from,
          'to' => $request->to
        ]
      ]
    ]);
  }

  public function review(Request $request, LeaveRequest $leaveRequest)
  {
    if (!$leaveRequest->isPending()) {
      throw ValidationException::withMessages([
        'status' => 'This leave request has already been processed.'
      ]);
    }

    $request->validate([
      'status' => 'required|in:approved,rejected',
      'notes' => 'nullable|string|max:500'
    ]);

    $now = Carbon::parse('2025-04-06 23:09:25');

    $leaveRequest->update([
      'status' => $request->status,
      'reviewed_by' => Auth::id(),
      'review_notes' => $request->notes,
      'reviewed_at' => $now
    ]);

    // Update employee status if leave starts today or is ongoing
    if ($request->status === 'approved' &&
      $leaveRequest->start_date->lte($now) &&
      $leaveRequest->end_date->gte($now)) {
      $leaveRequest->user->employeeProfile->update([
        'status' => 'on_leave'
      ]);
    }

    return back()->with('success',
      "Leave request has been {$request->status} successfully."
    );
  }
}
