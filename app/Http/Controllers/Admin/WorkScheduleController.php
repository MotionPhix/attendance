<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkScheduleRequest;
use App\Models\Department;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkScheduleController extends Controller
{
  /**
   * Display a listing of the work schedules.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $query = WorkSchedule::query()
      ->with('department');

    // Department filter
    if ($request->has('department') && $request->input('department')) {
      $query->where('department_id', $request->input('department'));
    }

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where('name', 'like', "%{$search}%")
        ->orWhereHas('department', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%");
        });
    }

    // Sorting
    $sortField = $request->input('sort_field', 'name');
    $sortDirection = $request->input('sort_direction', 'asc');

    if ($sortField === 'department') {
      $query->join('departments', 'work_schedules.department_id', '=', 'departments.id')
        ->orderBy('departments.name', $sortDirection)
        ->select('work_schedules.*');
    } else {
      $query->orderBy($sortField, $sortDirection);
    }

    // Pagination
    $workSchedules = $query->paginate(10)
      ->withQueryString();

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    return Inertia::render('Admin/WorkSchedules/Index', [
      'workSchedules' => $workSchedules,
      'departments' => $departments,
      'filters' => [
        'search' => $request->input('search', ''),
        'department' => $request->input('department', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
    ]);
  }

  /**
   * Show the form for creating a new work schedule.
   *
   * @return \Inertia\Response
   */
  public function create()
  {
    $departments = Department::orderBy('name')->get(['id', 'name']);

    return Inertia::render('Admin/WorkSchedules/Create', [
      'departments' => $departments,
      'daysOfWeek' => [
        ['value' => 0, 'label' => 'Sunday'],
        ['value' => 1, 'label' => 'Monday'],
        ['value' => 2, 'label' => 'Tuesday'],
        ['value' => 3, 'label' => 'Wednesday'],
        ['value' => 4, 'label' => 'Thursday'],
        ['value' => 5, 'label' => 'Friday'],
        ['value' => 6, 'label' => 'Saturday'],
      ],
    ]);
  }

  /**
   * Store a newly created work schedule in storage.
   *
   * @param WorkScheduleRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(WorkScheduleRequest $request)
  {
    // If this is set as default, unset other defaults for this department
    if ($request->is_default) {
      WorkSchedule::where('department_id', $request->department_id)
        ->where('is_default', true)
        ->update(['is_default' => false]);
    }

    WorkSchedule::create($request->validated());

    return redirect()->route('admin.work-schedules.index')
      ->with('success', 'Work schedule created successfully.');
  }

  /**
   * Display the specified work schedule.
   *
   * @param WorkSchedule $workSchedule
   * @return \Inertia\Response
   */
  public function show(WorkSchedule $workSchedule)
  {
    $workSchedule->load('department');

    return Inertia::render('Admin/WorkSchedules/Show', [
      'workSchedule' => $workSchedule,
      'daysOfWeek' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
      ],
    ]);
  }

  /**
   * Show the form for editing the specified work schedule.
   *
   * @param WorkSchedule $workSchedule
   * @return \Inertia\Response
   */
  public function edit(WorkSchedule $workSchedule)
  {
    $departments = Department::orderBy('name')->get(['id', 'name']);

    return Inertia::render('Admin/WorkSchedules/Edit', [
      'workSchedule' => $workSchedule,
      'departments' => $departments,
      'daysOfWeek' => [
        ['value' => 0, 'label' => 'Sunday'],
        ['value' => 1, 'label' => 'Monday'],
        ['value' => 2, 'label' => 'Tuesday'],
        ['value' => 3, 'label' => 'Wednesday'],
        ['value' => 4, 'label' => 'Thursday'],
        ['value' => 5, 'label' => 'Friday'],
        ['value' => 6, 'label' => 'Saturday'],
      ],
    ]);
  }

  /**
   * Update the specified work schedule in storage.
   *
   * @param WorkScheduleRequest $request
   * @param WorkSchedule $workSchedule
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(WorkScheduleRequest $request, WorkSchedule $workSchedule)
  {
    // If this is set as default, unset other defaults for this department
    if ($request->is_default && !$workSchedule->is_default) {
      WorkSchedule::where('department_id', $request->department_id)
        ->where('is_default', true)
        ->update(['is_default' => false]);
    }

    $workSchedule->update($request->validated());

    return redirect()->route('admin.work-schedules.index')
      ->with('success', 'Work schedule updated successfully.');
  }

  /**
   * Remove the specified work schedule from storage.
   *
   * @param WorkSchedule $workSchedule
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(WorkSchedule $workSchedule)
  {
    // Check if this is the default schedule
    if ($workSchedule->is_default) {
      return redirect()->back()
        ->with('error', 'Cannot delete the default work schedule. Please set another schedule as default first.');
    }

    $workSchedule->delete();

    return redirect()->route('admin.work-schedules.index')
      ->with('success', 'Work schedule deleted successfully.');
  }

  /**
   * Set a work schedule as the default for its department.
   *
   * @param WorkSchedule $workSchedule
   * @return \Illuminate\Http\RedirectResponse
   */
  public function setDefault(WorkSchedule $workSchedule)
  {
    // Unset other defaults for this department
    WorkSchedule::where('department_id', $workSchedule->department_id)
      ->where('is_default', true)
      ->update(['is_default' => false]);

    // Set this one as default
    $workSchedule->update(['is_default' => true]);

    return redirect()->back()
      ->with('success', 'Default work schedule updated successfully.');
  }
}
