<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EmployeeController extends Controller
{
  public function index(Request $request)
  {
    $query = Employee::with(['user', 'department'])
      ->when($request->search, function ($query, $search) {
        $query->whereHas('user', function ($query) use ($search) {
          $query->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
        })->orWhere('employee_id', 'like', "%{$search}%");
      })
      ->when($request->department, function ($query, $department) {
        $query->where('department_id', $department);
      })
      ->when($request->status, function ($query, $status) {
        $query->where('status', $status);
      });

    $employees = $query->paginate(10)
      ->withQueryString();

    return Inertia::render('admin/employees/Index', [
      'employees' => $employees,
      'departments' => Department::all(),
      'filters' => $request->only(['search', 'department', 'status']),
    ]);
  }

  public function show(Employee $employee)
  {
    $employee->load(['user', 'department']);

    return Inertia::render('admin/employees/Show', [
      'employee' => $employee,
      'departments' => Department::all(),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'department_id' => ['required', 'exists:departments,id'],
      'position' => ['required', 'string', 'max:255'],
      'employee_id' => ['required', 'string', 'max:255', 'unique:employees'],
      'join_date' => ['required', 'date'],
    ]);

    return DB::transaction(function () use ($request) {
      $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make(Str::random(12)),
      ]);

      $user->assignRole('employee');

      $employee = Employee::create([
        'user_id' => $user->id,
        'department_id' => $request->department_id,
        'position' => $request->position,
        'employee_id' => $request->employee_id,
        'join_date' => $request->join_date,
        'status' => 'active',
      ]);

      // TODO: Send welcome email with password reset link

      return $employee;
    });
  }

  public function update(Request $request, Employee $employee)
  {
    $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $employee->user_id],
      'department_id' => ['required', 'exists:departments,id'],
      'position' => ['required', 'string', 'max:255'],
      'status' => ['required', 'in:active,inactive,on_leave'],
      'phone' => ['nullable', 'string', 'max:255'],
      'address' => ['nullable', 'string', 'max:255'],
      'emergency_contact' => ['nullable', 'array'],
      'emergency_contact.name' => ['required_with:emergency_contact', 'string', 'max:255'],
      'emergency_contact.relationship' => ['required_with:emergency_contact', 'string', 'max:255'],
      'emergency_contact.phone' => ['required_with:emergency_contact', 'string', 'max:255'],
    ]);

    return DB::transaction(function () use ($request, $employee) {
      $employee->user->update([
        'name' => $request->name,
        'email' => $request->email,
      ]);

      $employee->update([
        'department_id' => $request->department_id,
        'position' => $request->position,
        'status' => $request->status,
        'phone' => $request->phone,
        'address' => $request->address,
        'emergency_contact' => $request->emergency_contact,
      ]);

      return $employee;
    });
  }

  public function destroy(Employee $employee)
  {
    $employee->update(['status' => 'inactive']);

    return redirect()->route('admin.employees.index');
  }
}
