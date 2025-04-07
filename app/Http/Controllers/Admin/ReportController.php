<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class ReportController extends Controller
{
  /**
   * The report service instance.
   *
   * @var ReportService
   */
  protected $reportService;

  /**
   * Create a new controller instance.
   *
   * @param ReportService $reportService
   * @return void
   */
  public function __construct(ReportService $reportService)
  {
    $this->reportService = $reportService;
  }

  /**
   * Display daily attendance report.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function dailyAttendance(Request $request)
  {
    $date = $request->input('date', Carbon::today()->format('Y-m-d'));
    $departmentId = $request->input('department_id');

    $report = $this->reportService->generateDailyAttendanceReport($date, $departmentId);

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    return Inertia::render('admin/reports/DailyAttendance', [
      'report' => $report,
      'departments' => $departments,
      'filters' => [
        'date' => $date,
        'department_id' => $departmentId,
      ],
    ]);
  }

  /**
   * Display monthly attendance report.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function monthlyAttendance(Request $request)
  {
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', Carbon::now()->year);
    $departmentId = $request->input('department_id');

    $report = $this->reportService->generateMonthlyAttendanceReport($month, $year, $departmentId);

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get available months for filter (last 12 months)
    $availableMonths = [];
    $currentDate = Carbon::now();
    for ($i = 0; $i < 12; $i++) {
      $date = $currentDate->copy()->subMonths($i);
      $availableMonths[] = [
        'month' => $date->month,
        'year' => $date->year,
        'name' => $date->format('F Y'),
      ];
    }

    return Inertia::render('Admin/Reports/MonthlyAttendance', [
      'report' => $report,
      'departments' => $departments,
      'availableMonths' => $availableMonths,
      'filters' => [
        'month' => (int)$month,
        'year' => (int)$year,
        'department_id' => $departmentId,
      ],
    ]);
  }

  /**
   * Display department attendance report.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function departmentAttendance(Request $request)
  {
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', Carbon::now()->year);

    $report = $this->reportService->generateDepartmentAttendanceReport($month, $year);

    // Get available months for filter (last 12 months)
    $availableMonths = [];
    $currentDate = Carbon::now();
    for ($i = 0; $i < 12; $i++) {
      $date = $currentDate->copy()->subMonths($i);
      $availableMonths[] = [
        'month' => $date->month,
        'year' => $date->year,
        'name' => $date->format('F Y'),
      ];
    }

    return Inertia::render('Admin/Reports/DepartmentAttendance', [
      'report' => $report,
      'availableMonths' => $availableMonths,
      'filters' => [
        'month' => (int)$month,
        'year' => (int)$year,
      ],
    ]);
  }

  /**
   * Display monthly salary report.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function monthlySalary(Request $request)
  {
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', Carbon::now()->year);
    $departmentId = $request->input('department_id');

    $report = $this->reportService->generateMonthlySalaryReport($month, $year, $departmentId);

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get available months for filter (last 12 months)
    $availableMonths = [];
    $currentDate = Carbon::now();
    for ($i = 0; $i < 12; $i++) {
      $date = $currentDate->copy()->subMonths($i);
      $availableMonths[] = [
        'month' => $date->month,
        'year' => $date->year,
        'name' => $date->format('F Y'),
      ];
    }

    return Inertia::render('Admin/Reports/MonthlySalary', [
      'report' => $report,
      'departments' => $departments,
      'availableMonths' => $availableMonths,
      'filters' => [
        'month' => (int)$month,
        'year' => (int)$year,
        'department_id' => $departmentId,
      ],
      'statuses' => [
        'pending' => 'Pending',
        'processed' => 'Processed',
        'paid' => 'Paid',
      ],
    ]);
  }

  /**
   * Display department salary report.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function departmentSalary(Request $request)
  {
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', Carbon::now()->year);

    $report = $this->reportService->generateDepartmentSalaryReport($month, $year);

    // Get available months for filter (last 12 months)
    $availableMonths = [];
    $currentDate = Carbon::now();
    for ($i = 0; $i < 12; $i++) {
      $date = $currentDate->copy()->subMonths($i);
      $availableMonths[] = [
        'month' => $date->month,
        'year' => $date->year,
        'name' => $date->format('F Y'),
      ];
    }

    return Inertia::render('Admin/Reports/DepartmentSalary', [
      'report' => $report,
      'availableMonths' => $availableMonths,
      'filters' => [
        'month' => (int)$month,
        'year' => (int)$year,
      ],
    ]);
  }

  /**
   * Display leave report.
   *
   * @param Request $request
   * @return \Inertia\Response
   */
  public function leaveReport(Request $request)
  {
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', Carbon::now()->year);
    $departmentId = $request->input('department_id');

    $report = $this->reportService->generateLeaveReport($month, $year, $departmentId);

    // Get departments for filter
    $departments = Department::orderBy('name')->get(['id', 'name']);

    // Get available months for filter (last 12 months)
    $availableMonths = [];
    $currentDate = Carbon::now();
    for ($i = 0; $i < 12; $i++) {
      $date = $currentDate->copy()->subMonths($i);
      $availableMonths[] = [
        'month' => $date->month,
        'year' => $date->year,
        'name' => $date->format('F Y'),
      ];
    }

    return Inertia::render('Admin/Reports/Leave', [
      'report' => $report,
      'departments' => $departments,
      'availableMonths' => $availableMonths,
      'filters' => [
        'month' => (int)$month,
        'year' => (int)$year,
        'department_id' => $departmentId,
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
   * Export report data.
   *
   * @param Request $request
   * @param string $type
   * @return \Illuminate\Http\Response
   */
  public function exportReport(Request $request, string $type)
  {
    $month = $request->input('month', Carbon::now()->month);
    $year = $request->input('year', Carbon::now()->year);
    $date = $request->input('date', Carbon::today()->format('Y-m-d'));
    $departmentId = $request->input('department_id');

    $data = [];
    $filename = '';

    switch ($type) {
      case 'daily-attendance':
        $data = $this->reportService->generateDailyAttendanceReport($date, $departmentId);
        $filename = 'daily-attendance-' . $data['date'] . '.csv';
        break;

      case 'monthly-attendance':
        $data = $this->reportService->generateMonthlyAttendanceReport($month, $year, $departmentId);
        $filename = 'monthly-attendance-' . $data['formatted_period'] . '.csv';
        break;

      case 'monthly-salary':
        $data = $this->reportService->generateMonthlySalaryReport($month, $year, $departmentId);
        $filename = 'monthly-salary-' . $data['formatted_period'] . '.csv';
        break;

      case 'leave':
        $data = $this->reportService->generateLeaveReport($month, $year, $departmentId);
        $filename = 'leave-report-' . $data['formatted_period'] . '.csv';
        break;

      default:
        abort(404, 'Report type not found');
    }

    $csv = $this->reportService->exportReportToCsv($type, $data);

    return Response::make($csv, 200, [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
  }
}
