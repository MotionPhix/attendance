<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payslip - {{ $salary->user->name }} - {{ $period }}</title>
  <style>
    body {
      font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
      font-size: 10pt;
      line-height: 1.4;
      color: #333;
      margin: 0;
      padding: 0;
    }
    .payslip {
      width: 100%;
      margin: 0 auto;
      padding: 10px;
    }
    .payslip-header {
      position: relative;
      padding-bottom: 15px;
      border-bottom: 2px solid #333;
      margin-bottom: 15px;
      height: 80px;
    }
    .company-info {
      position: absolute;
      left: 0;
      top: 0;
    }
    .company-logo {
      position: absolute;
      right: 0;
      top: 0;
      max-width: 150px;
      max-height: 70px;
    }
    .company-name {
      font-size: 18pt;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .payslip-title {
      text-align: center;
      font-size: 14pt;
      font-weight: bold;
      margin: 15px 0;
      text-transform: uppercase;
    }
    .employee-info {
      width: 100%;
      margin-bottom: 15px;
    }
    .employee-info:after {
      content: "";
      display: table;
      clear: both;
    }
    .employee-details, .payslip-details {
      float: left;
      width: 48%;
    }
    .info-row {
      margin-bottom: 5px;
    }
    .info-row:after {
      content: "";
      display: table;
      clear: both;
    }
    .info-label {
      float: left;
      font-weight: bold;
      width: 150px;
    }
    .info-value {
      float: left;
    }
    .salary-breakdown {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .salary-breakdown th, .salary-breakdown td {
      padding: 6px 10px;
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
      margin-top: 20px;
    }
    .summary-title {
      font-size: 12pt;
      font-weight: bold;
      margin-bottom: 8px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 3px;
    }
    .summary-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .summary-table th, .summary-table td {
      padding: 5px 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .summary-table th {
      background-color: #f5f5f5;
      font-weight: bold;
    }
    .footer {
      margin-top: 30px;
      padding-top: 15px;
      border-top: 1px solid #ddd;
      text-align: center;
      font-size: 9pt;
      color: #666;
    }
    .signature-section {
      margin-top: 40px;
      position: relative;
      height: 70px;
    }
    .signature-box {
      position: absolute;
      width: 45%;
    }
    .signature-box.left {
      left: 0;
    }
    .signature-box.right {
      right: 0;
    }
    .signature-line {
      border-top: 1px solid #333;
      margin-top: 40px;
      padding-top: 5px;
      text-align: center;
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
        <td width="33%">Present Days: {{ $attendanceSummary['present_days'] }}</td>
        <td width="33%">Late Arrivals: {{ $attendanceSummary['late_arrivals'] }}</td>
        <td width="33%">Early Departures: {{ $attendanceSummary['early_departures'] }}</td>
      </tr>
      <tr>
        <td>Total Late Minutes: {{ $attendanceSummary['total_late_minutes'] ?? 0 }}</td>
        <td>Total Early Departure: {{ $attendanceSummary['total_early_departure_minutes'] ?? 0 }}</td>
        <td>Working Days: {{ $attendanceSummary['total_working_days'] }}</td>
      </tr>
    </table>
  </div>

  <!-- Leave Summary -->
  <div class="summary-section">
    <div class="summary-title">Leave Summary</div>
    <table class="summary-table">
      <tr>
        <td width="33%">Paid Leave: {{ $leaveSummary['paid_leave_days'] }} days</td>
        <td width="33%">Unpaid Leave: {{ $leaveSummary['unpaid_leave_days'] }} days</td>
        <td width="33%">Total Leave: {{ $leaveSummary['paid_leave_days'] + $leaveSummary['unpaid_leave_days'] }} days</td>
      </tr>
    </table>
  </div>

  <!-- Signature Section -->
  <div class="signature-section">
    <div class="signature-box left">
      <div class="signature-line">Employee Signature</div>
    </div>
    <div class="signature-box right">
      <div class="signature-line">Authorized Signature</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <p>This is a computer-generated document. No signature is required.</p>
    <p>For any salary related queries, please contact the HR department.</p>
  </div>
</div>
</body>
</html>
