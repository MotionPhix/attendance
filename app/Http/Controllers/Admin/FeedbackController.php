<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FeedbackController extends Controller
{
  /**
   * Display a listing of the feedback.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $query = Feedback::with(['user.employeeProfile.department']);

    // Type filter
    if ($request->has('type') && $request->input('type')) {
      $query->where('type', $request->input('type'));
    }

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
      $query->whereDate('created_at', '>=', $request->input('start_date'));
    }

    if ($request->has('end_date') && $request->input('end_date')) {
      $query->whereDate('created_at', '<=', $request->input('end_date'));
    }

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('content', 'like', "%{$search}%")
          ->orWhereHas('user', function ($userQuery) use ($search) {
            $userQuery->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
          });
      });
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    if ($sortField === 'employee') {
      $query->join('users', 'feedback.user_id', '=', 'users.id')
        ->orderBy('users.name', $sortDirection)
        ->select('feedback.*');
    } elseif ($sortField === 'department') {
      $query->join('users', 'feedback.user_id', '=', 'users.id')
        ->join('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
        ->join('departments', 'employee_profiles.department_id', '=', 'departments.id')
        ->orderBy('departments.name', $sortDirection)
        ->select('feedback.*');
    } else {
      $query->orderBy($sortField, $sortDirection);
    }

    // Pagination
    $feedback = $query->paginate(10)
      ->withQueryString();

    // Get departments for filter
    $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);

    // Get feedback statistics
    $stats = [
      'total' => Feedback::count(),
      'pending' => Feedback::where('status', 'pending')->count(),
      'in_review' => Feedback::where('status', 'in_review')->count(),
      'resolved' => Feedback::where('status', 'resolved')->count(),
      'closed' => Feedback::where('status', 'closed')->count(),
      'by_type' => [
        'suggestion' => Feedback::where('type', 'suggestion')->count(),
        'complaint' => Feedback::where('type', 'complaint')->count(),
        'praise' => Feedback::where('type', 'praise')->count(),
        'question' => Feedback::where('type', 'question')->count(),
        'other' => Feedback::where('type', 'other')->count(),
      ],
    ];

    return Inertia::render('Admin/Feedback/Index', [
      'feedback' => $feedback,
      'departments' => $departments,
      'stats' => $stats,
      'filters' => [
        'search' => $request->input('search', ''),
        'type' => $request->input('type', ''),
        'status' => $request->input('status', ''),
        'department' => $request->input('department', ''),
        'start_date' => $request->input('start_date', ''),
        'end_date' => $request->input('end_date', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
      'types' => [
        'suggestion' => 'Suggestion',
        'complaint' => 'Complaint',
        'praise' => 'Praise',
        'question' => 'Question',
        'other' => 'Other',
      ],
      'statuses' => [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
      ],
    ]);
  }

  /**
   * Display the specified feedback.
   *
   * @param Feedback $feedback
   * @return \Inertia\Response
   */
  public function show(Feedback $feedback)
  {
    $feedback->load(['user.employeeProfile.department', 'responder']);

    // Get other feedback from the same user
    $userFeedback = Feedback::where('user_id', $feedback->user_id)
      ->where('id', '!=', $feedback->id)
      ->orderBy('created_at', 'desc')
      ->limit(5)
      ->get();

    return Inertia::render('Admin/Feedback/Show', [
      'feedback' => $feedback,
      'userFeedback' => $userFeedback,
      'types' => [
        'suggestion' => 'Suggestion',
        'complaint' => 'Complaint',
        'praise' => 'Praise',
        'question' => 'Question',
        'other' => 'Other',
      ],
      'statuses' => [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
      ],
    ]);
  }

  /**
   * Respond to the specified feedback.
   *
   * @param Request $request
   * @param Feedback $feedback
   * @return \Illuminate\Http\RedirectResponse
   */
  public function respond(Request $request, Feedback $feedback)
  {
    $request->validate([
      'response' => 'required|string|max:2000',
      'status' => 'required|in:in_review,resolved,closed',
    ]);

    $feedback->update([
      'response' => $request->response,
      'status' => $request->status,
      'responded_by' => Auth::id(),
      'responded_at' => Carbon::now(),
    ]);

    return redirect()->route('admin.feedback.show', $feedback)
      ->with('success', 'Response submitted successfully.');
  }

  /**
   * Update the status of the specified feedback.
   *
   * @param Request $request
   * @param Feedback $feedback
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateStatus(Request $request, Feedback $feedback)
  {
    $request->validate([
      'status' => 'required|in:pending,in_review,resolved,closed',
    ]);

    $feedback->update([
      'status' => $request->status,
    ]);

    return redirect()->back()
      ->with('success', 'Feedback status updated successfully.');
  }
}
