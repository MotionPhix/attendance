<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payslip - {{ $salary->user->name }} - {{ $period }}</title>
  <style>
    @import "https://fonts.googleapis.com/css?family=Helvetica:400,700&display=swap";
    @import "https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap";
    @import "https://fonts.googleapis.com/css?family=Open+Sans:400,700&display=swap";

    body {
      font-family: 'Roboto', 'Helvetica', 'Open Sans', sans-serif;
      font-size: 12px;
      line-height: 1.5;
      color: #333;
      margin: 0;
      padding: 0;
    }
    .payslip {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      border: 1px solid #ddd;
    }
    .payslip-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 20px;
      border-bottom: 2px solid #333;
      margin-bottom: 20px;
    }
    .company-info {
      flex: 1;
    }
    .company-logo {
      max-width: 150px;
      max-height: 80px;
    }
    .company-name {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .payslip-title {
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      margin: 20px 0;
      text-transform: uppercase;
    }
    .employee-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .employee-details, .payslip-details {
      flex: 1;
    }
    .info-row {
      display: flex;
      margin-bottom: 5px;
    }
    .info-label {
      font-weight: bold;
      width: 150px;
    }
    .info-value {
      flex: 1;
    }
    .salary-breakdown {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .salary-breakdown th, .salary-breakdown td {
      padding: 8px 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .salary-breakdown th {
      background-color: #f5f5f5;
      font-weight: bold;
    }
    .salary-breakdown .total-row {
      font-weight: bold;
      border-top: 2px solid #333;
    }
    .amount-column {
      text-align: right;
    }
    .summary-section {
      margin-top: 30px;
    }
    .summary-title {
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 10px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 5px;
    }
    .summary-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .summary-table th, .summary-table td {
      padding: 6px 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .summary-table th {
      background-color: #f5f5f5;
      font-weight: bold;
    }
    .footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid #ddd;
      text-align: center;
      font-size: 11px;
      color: #666;
    }
    .signature-section {
      display: flex;
      justify-content: space-between;
      margin-top: 50px;
    }
    .signature-box {
      width: 45%;
    }
    .signature-line {
      border-top: 1px solid #333;
      margin-top: 50px;
      padding-top: 5px;
      text-align: center;
    }
    @media print {
      body {
        font-size: 11px;
      }
      .payslip {
        border: none;
        padding: 0;
      }
      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body>
<div class="payslip">
  <!-- Header with Company Info -->
  <div class="payslip-header">
    <div class="company-info">
      <div class="company-name">{{ $company['name'] }}</div>
      <div>{{ $company['address'] }}</div>
      <div>Phone: {{ $company['phone'] }}</div>
      <div>Email: {{ $company['email'] }}</div>
    </div>
    @if($company['logo'])
      <img src="{{ $company['logo'] }}" alt="Company Logo" class="company-logo">
    @endif
  </div>

  <!-- Payslip Title -->
  <div class="payslip-title">Payslip for {{ $period }}</div>

  <!-- Employee Information -->
  <div class="employee-info">
    <div class="employee-details">
      <div class="info-row">
        <div class="info-label">Employee Name:</div>
        <div class="info-value">{{ $salary->user->name }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Employee ID:</div>
        <div class="info-value">{{ $salary->user->id }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Position:</div>
        <div class="info-value">{{ $salary->user->employeeProfile->position }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Department:</div>
        <div class="info-value">{{ $salary->user->employeeProfile->department->name }}</div>
      </div>
    </div>
    <div class="payslip-details">
      <div class="info-row">
        <div class="info-label">Payslip No:</div>
        <div class="info-value">{{ $salary->id }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Pay Period:</div>
        <div class="info-value">{{ $period }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Payment Date:</div>
        <div class="info-value">{{ $salary->paid_at ? date('F d, Y', strtotime($salary->paid_at)) : 'Pending' }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Payment Method:</div>
        <div class="info-value">Bank Transfer</div>
      </div>
    </div>
  </div>

  <!-- Salary Breakdown -->
  <table class="salary-breakdown">
    <thead>
    <tr>
      <th width="60%">Description</th>
      <th class="amount-column">Amount</th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td>Base Salary</td>
      <td class="amount-column">{{ number_format($salary->base_amount, 2) }}</td>
    </tr>
    @if($salary->overtime_pay > 0)
      <tr>
        <td>Overtime Pay</td>
        <td class="amount-column">{{ number_format($salary->overtime_pay, 2) }}</td>
      </tr>
    @endif
    @if($salary->bonuses > 0)
      <tr>
        <td>Bonuses</td>
        <td class="amount-column">{{ number_format($salary->bonuses, 2) }}</td>
      </tr>
    @endif
    <tr>
      <td><strong>Gross Salary</strong></td>
      <td class="amount-column"><strong>{{ number_format($salary->base_amount + $salary->overtime_pay + $salary->bonuses, 2) }}</strong></td>
    </tr>

    <!-- Deductions -->
    @if($salary->deductions > 0)
      <tr>
        <td>Deductions (Tax, Late Arrivals, Early Departures, etc.)</td>
        <td class="amount-column">-{{ number_format($salary->deductions, 2) }}</td>
      </tr>
    @endif

    <!-- Net Salary -->
    <tr class="total-row">
      <td>Net Salary</td>
      <td class="amount-column">{{ number_format($salary->net_amount, 2) }}</td>
    </tr>
    </tbody>
  </table>

  <!-- Attendance Summary -->
  <div class="summary-section">
    <div class="summary-title">Attendance Summary</div>
    <table class="summary-table">
      <tr>
        <td width="33%">Present Days: {{ $attendanceSummary['present_days'] ?? 0 }}</td>
        <td width="33%">Late Arrivals: {{ $attendanceSummary['late_arrivals'] ?? 0 }}</td>
        <td width="33%">Early Departures: {{ $attendanceSummary['early_departures'] ?? 0 }}</td>
      </tr>
      <tr>
        <td>Total Late Minutes: {{ $attendanceSummary['total_late_minutes'] ?? 0 }}</td>
        <td>Total Early Departure: {{ $attendanceSummary['total_early_departure_minutes'] ?? 0 }}</td>
        <td>Working Days: {{ $attendanceSummary['total_working_days'] ?? 0 }}</td>
      </tr>
    </table>
  </div>

  <!-- Leave Summary -->
  <div class="summary-section">
    <div class="summary-title">Leave Summary</div>
    <table class="summary-table">
      <tr>
        <td width="33%">Paid Leave: {{ $leaveSummary['paid_leave_days'] ?? 0 }} days</td>
        <td width="33%">Unpaid Leave: {{ $leaveSummary['unpaid_leave_days'] ?? 0 }} days</td>
        <td width="33%">Total Leave: {{ ($leaveSummary['paid_leave_days'] ?? 0) + ($leaveSummary['unpaid_leave_days'] ?? 0) }} days</td>
      </tr>
    </table>
  </div>

  <!-- Signature Section -->
  <div class="signature-section">
    <div class="signature-box">
      <div class="signature-line">Employee Signature</div>
    </div>
    <div class="signature-box">
      <div class="signature-line">Authorized Signature</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <p>This is a computer-generated document. No signature is required.</p>
    <p>For any salary related queries, please contact the HR department.</p>
  </div>

  <!-- Print Button (only visible on screen) -->
  <div class="no-print" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
      Print Payslip
    </button>
  </div>
</div>
</body>
</html>
