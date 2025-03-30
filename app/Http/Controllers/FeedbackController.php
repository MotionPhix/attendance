<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedbackRequest;
use App\Models\Feedback;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FeedbackController extends Controller
{
  use AuthorizesRequests;

  /**
   * Display a listing of the feedback.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $user = Auth::user();

    $query = Feedback::where('user_id', $user->id);

    // Type filter
    if ($request->has('type') && $request->input('type')) {
      $query->where('type', $request->input('type'));
    }

    // Status filter
    if ($request->has('status') && $request->input('status')) {
      $query->where('status', $request->input('status'));
    }

    // Sorting
    $sortField = $request->input('sort_field', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $feedback = $query->paginate(10)
      ->withQueryString();

    return Inertia::render('Feedback/Index', [
      'feedback' => $feedback,
      'filters' => [
        'type' => $request->input('type', ''),
        'status' => $request->input('status', ''),
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
   * Show the form for creating a new feedback.
   *
   * @return \Inertia\Response
   */
  public function create()
  {
    return Inertia::render('Feedback/Create', [
      'types' => [
        'suggestion' => 'Suggestion',
        'complaint' => 'Complaint',
        'praise' => 'Praise',
        'question' => 'Question',
        'other' => 'Other',
      ],
    ]);
  }

  /**
   * Store a newly created feedback in storage.
   *
   * @param FeedbackRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(FeedbackRequest $request)
  {
    $user = Auth::user();

    Feedback::create([
      'user_id' => $user->id,
      'type' => $request->type,
      'content' => $request->content,
      'status' => 'pending',
    ]);

    return redirect()->route('feedback.index')
      ->with('success', 'Feedback submitted successfully.');
  }

  /**
   * Display the specified feedback.
   *
   * @param Feedback $feedback
   * @return \Inertia\Response
   */
  public function show(Feedback $feedback)
  {
    $this->authorize('view', $feedback);

    return Inertia::render('Feedback/Show', [
      'feedback' => $feedback,
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
   * Show the form for editing the specified feedback.
   *
   * @param Feedback $feedback
   * @return \Inertia\Response
   */
  public function edit(Feedback $feedback)
  {
    $this->authorize('update', $feedback);

    // Only allow editing if feedback is still pending
    if ($feedback->status !== 'pending') {
      return redirect()->route('feedback.show', $feedback)
        ->with('error', 'Cannot edit feedback that is already being processed.');
    }

    return Inertia::render('Feedback/Edit', [
      'feedback' => $feedback,
      'types' => [
        'suggestion' => 'Suggestion',
        'complaint' => 'Complaint',
        'praise' => 'Praise',
        'question' => 'Question',
        'other' => 'Other',
      ],
    ]);
  }

  /**
   * Update the specified feedback in storage.
   *
   * @param FeedbackRequest $request
   * @param Feedback $feedback
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(FeedbackRequest $request, Feedback $feedback)
  {
    $this->authorize('update', $feedback);

    // Only allow updating if feedback is still pending
    if ($feedback->status !== 'pending') {
      return redirect()->route('feedback.show', $feedback)
        ->with('error', 'Cannot update feedback that is already being processed.');
    }

    $feedback->update([
      'type' => $request->type,
      'content' => $request->content,
    ]);

    return redirect()->route('feedback.index')
      ->with('success', 'Feedback updated successfully.');
  }

  /**
   * Remove the specified feedback from storage.
   *
   * @param Feedback $feedback
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(Feedback $feedback)
  {
    $this->authorize('delete', $feedback);

    // Only allow deletion if feedback is still pending
    if ($feedback->status !== 'pending') {
      return redirect()->route('feedback.index')
        ->with('error', 'Cannot delete feedback that is already being processed.');
    }

    $feedback->delete();

    return redirect()->route('feedback.index')
      ->with('success', 'Feedback deleted successfully.');
  }
}
