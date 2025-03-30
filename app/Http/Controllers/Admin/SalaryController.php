<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\SalaryRecord;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use App\Services\SalaryCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class SalaryController extends Controller
{
  public function __construct(
    private AttendanceService $attendanceService,
    private LeaveService $leaveService,
  ) {}

  /**
   * Display a listing of salary records.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function index(Request $request)
  {
    // Get month and year from request or use current
    $month = $request->input('month', now()->month);
    $year = $request->input('year', now()->year);

    $query = SalaryRecord::query()
      ->with(['user.employeeProfile.department'])
      ->where('month', $month)
      ->where('year', $year);

    // Department filter
    if ($request->has('department') && $request->input('department')) {
      $query->whereHas('user.employeeProfile', function ($q) use ($request) {
        $q->where('department_id', $request->input('department'));
      });
    }

    // Status filter
    if ($request->has('status') && $request->input('status')) {
      $query->where('status', $request->input('status'));
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
    $sortField = $request->input('sort_field', 'net_amount');
    $sortDirection = $request->input('sort_direction', 'desc');

    if ($sortField === 'employee') {
      $query->join('users', 'salary_records.user_id', '=', 'users.id')
        ->orderBy('users.name', $sortDirection)
        ->select('salary_records.*');
    } elseif ($sortField === 'department') {
      $query->join('users', 'salary_records.user_id', '=', 'users.id')
        ->join('employee_profiles', 'users.id', '=', 'employee_profiles.user_id')
        ->join('departments', 'employee_profiles.department_id', '=', 'departments.id')
        ->orderBy('departments.name', $sortDirection)
        ->select('salary_records.*');
    } else {
      $query->orderBy($sortField, $sortDirection);
    }

    // Pagination
    $salaries = $query->paginate(15)
      ->withQueryString();

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get summary statistics
    $summary = [
      'total_employees' => User::whereHas('employeeProfile')->count(),
      'processed_employees' => $salaries->total(),
      'total_base_salary' => SalaryRecord::where('month', $month)
        ->where('year', $year)
        ->sum('base_amount'),
      'total_deductions' => SalaryRecord::where('month', $month)
        ->where('year', $year)
        ->sum('deductions'),
      'total_bonuses' => SalaryRecord::where('month', $month)
        ->where('year', $year)
        ->sum('bonuses'),
      'total_overtime_pay' => SalaryRecord::where('month', $month)
        ->where('year', $year)
        ->sum('overtime_pay'),
      'total_net_amount' => SalaryRecord::where('month', $month)
        ->where('year', $year)
        ->sum('net_amount'),
    ];

    return Inertia::render('admin/salaries/Index', [
      'salaries' => $salaries,
      'departments' => $departments,
      'summary' => $summary,
      'filters' => [
        'search' => $request->input('search', ''),
        'department' => $request->input('department', ''),
        'status' => $request->input('status', ''),
        'sort_field' => $sortField,
        'sort_direction' => $sortDirection,
        'month' => (int) $month,
        'year' => (int) $year,
      ],
      'statuses' => [
        'pending' => 'Pending',
        'processed' => 'Processed',
        'paid' => 'Paid',
      ],
      'currentPeriod' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
    ]);
  }

  /**
   * Show the form for generating salaries.
   *
   * @return \Inertia\Response
   */
  public function generate()
  {
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get current month and previous month
    $currentMonth = now()->month;
    $currentYear = now()->year;

    $previousMonth = now()->subMonth();
    $prevMonth = $previousMonth->month;
    $prevYear = $previousMonth->year;

    // Get employee counts
    $totalEmployees = EmployeeProfile::where('status', 'active')->count();
    $processedEmployees = SalaryRecord::where('month', $currentMonth)
      ->where('year', $currentYear)
      ->count();

    // Get salary settings from the settings table or use defaults
    $salarySettings = [
      'overtime_rate' => setting('overtime_rate', 1.5),
      'weekend_overtime_rate' => setting('weekend_overtime_rate', 2.0),
      'holiday_overtime_rate' => setting('holiday_overtime_rate', 2.5),
      'late_deduction_method' => setting('late_deduction_method', 'per_minute'),
      'late_deduction_amount' => setting('late_deduction_amount', 0),
      'early_departure_deduction_method' => setting('early_departure_deduction_method', 'per_minute'),
      'early_departure_deduction_amount' => setting('early_departure_deduction_amount', 0),
      'tax_calculation_method' => setting('tax_calculation_method', 'progressive'),
    ];

    // Get months for dropdown
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
      $months[] = [
        'value' => $i,
        'label' => Carbon::createFromDate(null, $i, 1)->format('F')
      ];
    }

    // Get years for dropdown (current year and 5 years back)
    $years = [];
    $currentYearValue = (int)$currentYear;
    for ($i = $currentYearValue - 5; $i <= $currentYearValue; $i++) {
      $years[] = [
        'value' => $i,
        'label' => (string)$i
      ];
    }

    // Get pending salaries for the current month
    $pendingSalaries = SalaryRecord::where('month', $currentMonth)
      ->where('year', $currentYear)
      ->where('status', 'pending')
      ->with('user:id,name')
      ->get(['id', 'user_id', 'month', 'year', 'created_at'])
      ->map(function ($salary) {
        return [
          'id' => $salary->id,
          'employee_name' => $salary->user->name,
          'month' => $salary->month,
          'year' => $salary->year,
          'created_at' => $salary->created_at->format('M d, Y')
        ];
      });

    return Inertia::render('admin/salaries/Generate', [
      'departments' => $departments,
      'months' => $months,
      'years' => $years,
      'currentMonth' => $currentMonth,
      'currentYear' => $currentYear,
      'prevMonth' => $prevMonth,
      'prevYear' => $prevYear,
      'salarySettings' => $salarySettings,
      'totalEmployees' => $totalEmployees,
      'processedEmployees' => $processedEmployees,
      'pendingSalaries' => $pendingSalaries,
      'currentPeriod' => Carbon::createFromDate($currentYear, $currentMonth, 1)->format('F Y'),
      'previousPeriod' => Carbon::createFromDate($prevYear, $prevMonth, 1)->format('F Y'),
      'employeeCount' => $totalEmployees,
    ]);
  }

  /**
   * Preview salary calculations before generating them.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function preview(Request $request)
  {
    $validated = $request->validate([
      'month' => 'required|integer|between:1,12',
      'year' => 'required|integer|min:2000',
      'department_id' => 'nullable|exists:departments,id',
      'include_inactive' => 'boolean',
      'recalculate_existing' => 'boolean',
    ]);

    $month = $validated['month'];
    $year = $validated['year'];
    $departmentId = $validated['department_id'] ?? null;
    $includeInactive = $validated['include_inactive'] ?? false;
    $recalculateExisting = $validated['recalculate_existing'] ?? false;

    // Get employees query
    $query = User::whereHas('employeeProfile');

    // Filter by department if specified
    if ($departmentId) {
      $query->whereHas('employeeProfile', function ($q) use ($departmentId) {
        $q->where('department_id', $departmentId);
      });
    }

    // Filter by active status unless include_inactive is true
    if (!$includeInactive) {
      $query->whereHas('employeeProfile', function ($q) {
        $q->where('status', 'active');
      });
    }

    // Exclude employees who already have salary records for this month/year
    // unless recalculate_existing is true
    if (!$recalculateExisting) {
      $query->whereDoesntHave('salaryRecords', function ($q) use ($month, $year) {
        $q->where('month', $month)->where('year', $year);
      });
    }

    // Get employees with their profiles and departments
    $employees = $query->with(['employeeProfile.department'])->get();

    // Calculate estimated salary for each employee
    $employeeData = [];
    $salaryService = app(SalaryCalculationService::class);

    foreach ($employees as $employee) {
      // Get estimated salary calculation
      try {
        $salaryEstimate = $salaryService->calculateEmployeeSalary(
          $employee->id,
          $month,
          $year,
          true // preview mode
        );

        $employeeData[] = [
          'id' => $employee->id,
          'name' => $employee->name,
          'position' => $employee->employeeProfile->position,
          'department' => $employee->employeeProfile->department->name,
          'base_salary' => $employee->employeeProfile->base_salary,
          'estimated_deductions' => $salaryEstimate['deductions'],
          'estimated_bonuses' => $salaryEstimate['bonuses'],
          'estimated_overtime' => $salaryEstimate['overtime_pay'],
          'estimated_net_salary' => $salaryEstimate['net_amount'],
        ];
      } catch (\Exception $e) {
        Log::error('Error calculating salary preview: ' . $e->getMessage(), [
          'user_id' => $employee->id,
          'month' => $month,
          'year' => $year,
        ]);
      }
    }

    // Return preview data
    return response()->json([
      'period' => [
        'month' => $month,
        'year' => $year,
        'month_name' => Carbon::createFromDate($year, $month, 1)->format('F'),
      ],
      'employees' => $employeeData,
      'total_employees' => count($employeeData),
      'department_id' => $departmentId,
      'department_name' => $departmentId ? Department::find($departmentId)->name : 'All Departments',
    ]);
  }

  /**
   * Mark multiple salary records as paid.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function markAsPaid(Request $request)
  {
    $validated = $request->validate([
      'salary_ids' => 'required|array',
      'salary_ids.*' => 'exists:salary_records,id',
    ]);

    $salaryIds = $validated['salary_ids'];
    $count = 0;

    foreach ($salaryIds as $id) {
      $salary = SalaryRecord::find($id);

      if ($salary && $salary->status !== 'paid') {
        $salary->update([
          'status' => 'paid',
          'paid_at' => now(),
        ]);
        $count++;
      }
    }

    return redirect()->back()
      ->with('success', "{$count} salary records have been marked as paid.");
  }

  /**
   * Mark a single salary record as paid.
   *
   * @param  int  $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function markSingleAsPaid($id)
  {
    $salary = SalaryRecord::findOrFail($id);

    if ($salary->status !== 'paid') {
      $salary->update([
        'status' => 'paid',
        'paid_at' => now(),
      ]);
    }

    return redirect()->back()
      ->with('success', 'Salary record has been marked as paid.');
  }

  /**
   * Process salary generation.
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function processGeneration(Request $request)
  {
    $validated = $request->validate([
      'month' => 'required|integer|min:1|max:12',
      'year' => 'required|integer|min:2000|max:2100',
      'department_id' => 'nullable|exists:departments,id',
      'include_inactive' => 'boolean',
      'recalculate_existing' => 'boolean',
      'employee_ids' => 'required|array',
      'employee_ids.*' => 'exists:users,id',
    ]);

    $month = $validated['month'];
    $year = $validated['year'];
    $employeeIds = $validated['employee_ids'];
    $recalculateExisting = $validated['recalculate_existing'] ?? false;

    // Get the salary calculation service
    $salaryService = app(SalaryCalculationService::class);

    // Track success and failures
    $successCount = 0;
    $failureCount = 0;

    // Process each employee
    foreach ($employeeIds as $employeeId) {
      try {
        // Check if salary record already exists
        $existingSalary = SalaryRecord::where('user_id', $employeeId)
          ->where('month', $month)
          ->where('year', $year)
          ->first();

        if ($existingSalary && !$recalculateExisting) {
          // Skip if record exists and we're not recalculating
          $failureCount++;
          continue;
        }

        // Calculate salary
        $salaryData = $salaryService->calculateEmployeeSalary($employeeId, $month, $year);

        if ($existingSalary) {
          // Update existing record
          $existingSalary->update([
            'base_amount' => $salaryData['base_amount'],
            'deductions' => $salaryData['deductions'],
            'bonuses' => $salaryData['bonuses'],
            'overtime_pay' => $salaryData['overtime_pay'],
            'net_amount' => $salaryData['net_amount'],
            'status' => 'pending',
            'processed_at' => now(),
          ]);
        } else {
          // Create new record
          SalaryRecord::create([
            'user_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'base_amount' => $salaryData['base_amount'],
            'deductions' => $salaryData['deductions'],
            'bonuses' => $salaryData['bonuses'],
            'overtime_pay' => $salaryData['overtime_pay'],
            'net_amount' => $salaryData['net_amount'],
            'status' => 'pending',
            'processed_at' => now(),
          ]);
        }

        $successCount++;
      } catch (\Exception $e) {
        // Log the error
        Log::error('Salary generation failed for employee ID ' . $employeeId . ': ' . $e->getMessage());
        $failureCount++;
      }
    }

    // Redirect with status message
    $periodFormatted = Carbon::createFromDate($year, $month, 1)->format('F Y');

    if ($successCount > 0 && $failureCount === 0) {
      return redirect()->route('admin.salaries.index')
        ->with('success', "Successfully generated {$successCount} salary records for {$periodFormatted}.");
    } elseif ($successCount > 0 && $failureCount > 0) {
      return redirect()->route('admin.salaries.index')
        ->with('warning', "Generated {$successCount} salary records for {$periodFormatted}, but {$failureCount} failed. Check logs for details.");
    } else {
      return redirect()->route('admin.salaries.generate')
        ->with('error', "Failed to generate salary records. Please try again or contact support.");
    }
  }

  /**
   * Show the specified salary record.
   *
   * @param int $id
   * @return \Inertia\Response
   */
  public function show($id)
  {
    $salary = SalaryRecord::with(['user.employeeProfile.department'])->findOrFail($id);

    // Get attendance data for the month
    $startDate = Carbon::createFromDate($salary->year, $salary->month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($salary->year, $salary->month, 1)->endOfMonth();

    // Get attendance summary
    $attendanceService = app(AttendanceService::class);
    $attendanceSummary = $attendanceService->getAttendanceSummary(
      $salary->user_id,
      $startDate,
      $endDate
    );

    // Get leave summary
    $leaveService = app(LeaveService::class);
    $leaveSummary = $leaveService->getLeaveSummary(
      $salary->user_id,
      $startDate,
      $endDate
    );

    return Inertia::render('admin/salaries/Show', [
      'salary' => $salary,
      'attendanceSummary' => $attendanceSummary,
      'leaveSummary' => $leaveSummary,
      'period' => Carbon::createFromDate($salary->year, $salary->month, 1)->format('F Y'),
    ]);
  }

  /**
   * Update the specified salary record.
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request, $id)
  {
    $salary = SalaryRecord::findOrFail($id);

    $validated = $request->validate([
      'status' => 'required|in:pending,processed,paid',
      'paid_at' => 'nullable|date|required_if:status,paid',
      'payment_reference' => 'nullable|string|max:255',
    ]);

    $updateData = [
      'status' => $validated['status'],
    ];

    if ($validated['status'] === 'paid') {
      $updateData['paid_at'] = $validated['paid_at'] ?? now();
      $updateData['payment_reference'] = $validated['payment_reference'] ?? null;
    }

    $salary->update($updateData);

    return redirect()->back()->with('success', 'Salary record updated successfully.');
  }

  /**
   * Recalculate a salary record.
   *
   * @param int $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function recalculate($id)
  {
    $salary = SalaryRecord::findOrFail($id);

    try {
      $salaryService = app(SalaryCalculationService::class);

      // Calculate salary
      $salaryData = $salaryService->calculateEmployeeSalary(
        $salary->user_id,
        $salary->month,
        $salary->year
      );

      // Update salary record
      $salary->update([
        'base_amount' => $salaryData['base_amount'],
        'deductions' => $salaryData['deductions'],
        'bonuses' => $salaryData['bonuses'],
        'overtime_pay' => $salaryData['overtime_pay'],
        'net_amount' => $salaryData['net_amount'],
        'status' => 'processed',
        'processed_at' => now()
      ]);

      return redirect()->back()
        ->with('success', 'Salary recalculated successfully.');
    } catch (\Exception $e) {
      return redirect()->back()
        ->with('error', 'Failed to recalculate salary: ' . $e->getMessage());
    }
  }

  /**
   * Generate a printable payslip for the specified salary record.
   *
   * @param int $id
   * @return \Illuminate\View\View
   */
  public function generatePayslip($id): \Illuminate\View\View
  {
    $salary = SalaryRecord::with(['user.employeeProfile.department'])
      ->findOrFail($id);

    // Get attendance summary for the month
    $month = $salary->month;
    $year = $salary->year;
    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

    // Get attendance summary
    $attendanceSummary = $this->attendanceService->getAttendanceSummary(
      $salary->user_id,
      $startDate,
      $endDate
    );

    // Get leave summary for the month
    $leaveSummary = $this->leaveService->getLeaveSummary(
      $salary->user_id,
      $startDate,
      $endDate
    );

    // Get company information from settings
    $companyName = setting('company_name', config('app.name'));
    $companyAddress = setting('company_address', '');
    $companyPhone = setting('company_phone', '');
    $companyEmail = setting('company_email', config('mail.from.address'));
    $companyLogo = setting('company_logo', '');

    // Format period
    $periodFormatted = Carbon::createFromDate($year, $month, 1)->format('F Y');

    return view('admin.salaries.payslip', [
      'salary' => $salary,
      'attendanceSummary' => $attendanceSummary,
      'leaveSummary' => $leaveSummary,
      'period' => $periodFormatted,
      'company' => [
        'name' => $companyName,
        'address' => $companyAddress,
        'phone' => $companyPhone,
        'email' => $companyEmail,
        'logo' => $companyLogo ? Storage::url($companyLogo) : null,
      ],
    ]);
  }


  /**
   * Download the payslip as PDF.
   *
   * @param int $id
   * @return \Illuminate\Http\Response
   */
  public function downloadPayslip($id)
  {
    $salary = SalaryRecord::with(['user.employeeProfile.department'])
      ->findOrFail($id);

    // Get attendance data for the month
    $startDate = Carbon::createFromDate($salary->year, $salary->month, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($salary->year, $salary->month, 1)->endOfMonth();

    $attendanceSummary = $this->attendanceService->getAttendanceSummary(
      $salary->user_id,
      $startDate,
      $endDate
    );

    // Get leave summary for the month
    $leaveSummary = $this->leaveService->getLeaveSummary(
      $salary->user_id,
      $startDate,
      $endDate
    );

    // Get company information from settings
    $companyName = setting('company_name', config('app.name'));
    $companyAddress = setting('company_address', '');
    $companyPhone = setting('company_phone', '');
    $companyEmail = setting('company_email', config('mail.from.address'));
    $companyLogo = setting('company_logo', '');

    // Format period
    $periodFormatted = Carbon::createFromDate($salary->year, $salary->month, 1)->format('F Y');

    // Generate PDF
    $pdf = PDF::loadView('admin.salaries.payslip-pdf', [
      'salary' => $salary,
      'attendanceSummary' => $attendanceSummary,
      'leaveSummary' => $leaveSummary,
      'period' => $periodFormatted,
      'company' => [
        'name' => $companyName,
        'address' => $companyAddress,
        'phone' => $companyPhone,
        'email' => $companyEmail,
        'logo' => $companyLogo ? Storage::url($companyLogo) : null,
      ],
    ]);

    return $pdf->download("payslip-{$salary->user->name}-{$salary->month}-{$salary->year}.pdf");
  }

  /**
   * View department salary statistics.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function departmentStats(Request $request)
  {
    // Get month and year from request or use current
    $month = $request->input('month', now()->month);
    $year = $request->input('year', now()->year);

    $salaryService = app(SalaryCalculationService::class);
    $comparison = $salaryService->getDepartmentSalaryComparison($month, $year);

    // Get departments
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get detailed stats for selected department
    $selectedDepartment = $request->input('department_id');
    $departmentStats = null;

    if ($selectedDepartment) {
      $departmentStats = $salaryService->getDepartmentSalaryStats(
        $selectedDepartment,
        $month,
        $year
      );
    }

    return Inertia::render('admin/salaries/DepartmentStats', [
      'comparison' => $comparison,
      'departments' => $departments,
      'selectedDepartment' => $selectedDepartment ? Department::find($selectedDepartment) : null,
      'departmentStats' => $departmentStats,
      'filters' => [
        'month' => (int) $month,
        'year' => (int) $year,
        'department_id' => $selectedDepartment,
      ],
      'currentPeriod' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
    ]);
  }
}
