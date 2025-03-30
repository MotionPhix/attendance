<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
  /**
   * Display a listing of the departments.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    $query = Department::query();

    // Search functionality
    if ($request->has('search')) {
      $search = $request->input('search');
      $query->where('name', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
    }

    // Sorting
    $sortField = $request->input('sort_field', 'name');
    $sortDirection = $request->input('sort_direction', 'asc');
    $query->orderBy($sortField, $sortDirection);

    // Pagination
    $departments = $query->withCount('employees')
      ->paginate(10)
      ->withQueryString();

    return Inertia::render('Admin/Departments/Index', [
      'departments' => $departments,
      'filters' => [
        'search' => $request->input('search', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
      ],
    ]);
  }

  /**
   * Show the form for creating a new department.
   *
   * @return \Inertia\Response
   */
  public function create()
  {
    return Inertia::render('Admin/Departments/Create');
  }

  /**
   * Store a newly created department in storage.
   *
   * @param DepartmentRequest $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(DepartmentRequest $request)
  {
    $department = Department::create($request->validated());

    return redirect()->route('admin.departments.index')
      ->with('success', 'Department created successfully.');
  }

  /**
   * Display the specified department.
   *
   * @param Department $department
   * @return \Inertia\Response
   */
  public function show(Department $department)
  {
    $department->load(['employees', 'workSchedules']);

    return Inertia::render('Admin/Departments/Show', [
      'department' => $department,
    ]);
  }

  /**
   * Show the form for editing the specified department.
   *
   * @param Department $department
   * @return \Inertia\Response
   */
  public function edit(Department $department)
  {
    return Inertia::render('Admin/Departments/Edit', [
      'department' => $department,
    ]);
  }

  /**
   * Update the specified department in storage.
   *
   * @param DepartmentRequest $request
   * @param Department $department
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(DepartmentRequest $request, Department $department)
  {
    $department->update($request->validated());

    return redirect()->route('admin.departments.index')
      ->with('success', 'Department updated successfully.');
  }

  /**
   * Remove the specified department from storage.
   *
   * @param Department $department
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(Department $department)
  {
    // Check if department has employees
    if ($department->employees()->count() > 0) {
      return redirect()->back()
        ->with('error', 'Cannot delete department with assigned employees.');
    }

    $department->delete();

    return redirect()->route('admin.departments.index')
      ->with('success', 'Department deleted successfully.');
  }

  /**
   * Get department statistics.
   *
   * @param Department $department
   * @return \Inertia\Response
   */
  public function statistics(Department $department)
  {
    // Get attendance statistics
    $attendanceService = app(\App\Services\AttendanceService::class);
    $startDate = now()->startOfMonth();
    $endDate = now()->endOfMonth();

    $attendanceStats = $attendanceService->generateDepartmentReport(
      $department->id,
      $startDate,
      $endDate
    );

    // Get salary statistics
    $salaryService = app(\App\Services\SalaryCalculationService::class);
    $salaryStats = $salaryService->getDepartmentSalaryStats(
      $department->id,
      now()->month,
      now()->year
    );

    return Inertia::render('Admin/Departments/Statistics', [
      'department' => $department,
      'attendanceStats' => $attendanceStats,
      'salaryStats' => $salaryStats,
      'period' => [
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'month' => now()->month,
        'year' => now()->year,
      ],
    ]);
  }
}
