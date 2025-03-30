<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payslip - {{ $salary->user->name }} - {{ $period }}</title>
  <style>
    body {
      font-family: 'Helvetica', 'Arial', sans-serif;
      font-size: 12px;
      line-height: 1.5;
      color: #333;
      margin: 0;
      padding: 0;
    }
    .payslip {
      width: 100%;
      margin: 0 auto;
      padding: 20px;
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
    .deduction-details {
      font-size: 10px;
      color: #666;
      font-style: italic;
      margin-top: 2px;
    }
    .section-title {
      font-weight: bold;
      margin-top: 10px;
      margin-bottom: 5px;
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
  <div class="summary-title">Salary Breakdown</div>
  <table class="salary-breakdown">
    <thead>
    <tr>
      <th width="60%">Description</th>
      <th class="amount-column">Amount</th>
    </tr>
    </thead>
    <tbody>
    <!-- Earnings -->
    <tr>
      <td colspan="2" class="section-title">Earnings</td>
    </tr>
    <tr>
      <td>Base Salary</td>
      <td class="amount-column">{{ number_format($salary->base_amount, 2) }}</td>
    </tr>
    @if($salary->overtime_pay > 0)
      <tr>
        <td>
          Overtime Pay
          <div class="deduction-details">
            Based on {{ isset($salary->details) && isset(json_decode($salary->details, true)['overtime_hours']) ? json_decode($salary->details, true)['overtime_hours'] : 'calculated' }} overtime hours
          </div>
        </td>
        <td class="amount-column">{{ number_format($salary->overtime_pay, 2) }}</td>
      </tr>
    @endif
    @if($salary->bonuses > 0)
      <tr>
        <td>
          Bonuses
          @if(isset($salary->details) && isset(json_decode($salary->details, true)['bonuses']))
            @php $bonusDetails = json_decode($salary->details, true)['bonuses']; @endphp
            <div class="deduction-details">
              @if(isset($bonusDetails['performance']) && $bonusDetails['performance'] > 0)
                Performance bonus: {{ number_format($bonusDetails['performance'], 2) }}<br>
              @endif
              @if(isset($bonusDetails['attendance']) && $bonusDetails['attendance'] > 0)
                Attendance bonus: {{ number_format($bonusDetails['attendance'], 2) }}<br>
              @endif
              @if(isset($bonusDetails['other']) && $bonusDetails['other'] > 0)
                Other bonuses: {{ number_format($bonusDetails['other'], 2) }}
              @endif
            </div>
          @endif
        </td>
        <td class="amount-column">{{ number_format($salary->bonuses, 2) }}</td>
      </tr>
    @endif
    <tr>
      <td><strong>Total Earnings</strong></td>
      <td class="amount-column"><strong>{{ number_format($salary->base_amount + $salary->overtime_pay + $salary->bonuses, 2) }}</strong></td>
    </tr>

    <!-- Deductions -->
    <tr>
      <td colspan="2" class="section-title">Deductions</td>
    </tr>
    @if($salary->deductions > 0)
      @php
        $deductionDetails = isset($salary->details) ? json_decode($salary->details, true)['deductions'] ?? [] : [];
        $hasDetailedDeductions = !empty($deductionDetails) && is_array($deductionDetails);
      @endphp

      @if($hasDetailedDeductions)
        @if(isset($deductionDetails['attendance']) && $deductionDetails['attendance'] > 0)
          <tr>
            <td>
              Attendance Deductions
              <div class="deduction-details">
                Based on {{ $attendanceSummary['late_arrivals'] ?? 0 }} late arrivals and {{ $attendanceSummary['early_departures'] ?? 0 }} early departures
              </div>
            </td>
            <td class="amount-column">-{{ number_format($deductionDetails['attendance'], 2) }}</td>
          </tr>
        @endif

        @if(isset($deductionDetails['leave']) && $deductionDetails['leave'] > 0)
          <tr>
            <td>
              Leave Deductions
              <div class="deduction-details">
                Based on {{ $leaveSummary['unpaid_leave_days'] ?? 0 }} unpaid leave days
              </div>
            </td>
            <td class="amount-column">-{{ number_format($deductionDetails['leave'], 2) }}</td>
          </tr>
        @endif

        @if(isset($deductionDetails['tax']) && $deductionDetails['tax'] > 0)
          <tr>
            <td>
              Tax Deductions
              <div class="deduction-details">
                Income tax based on salary bracket
              </div>
            </td>
            <td class="amount-column">-{{ number_format($deductionDetails['tax'], 2) }}</td>
          </tr>
        @endif

        @if(isset($deductionDetails['other']) && $deductionDetails['other'] > 0)
          <tr>
            <td>
              Other Deductions
            </td>
            <td class="amount-column">-{{ number_format($deductionDetails['other'], 2) }}</td>
          </tr>
        @endif
      @else
        <tr>
          <td>Total Deductions</td>
          <td class="amount-column">-{{ number_format($salary->deductions, 2) }}</td>
        </tr>
      @endif
    @else
      <tr>
        <td>No Deductions</td>
        <td class="amount-column">0.00</td>
      </tr>
    @endif

    <tr>
      <td><strong>Total Deductions</strong></td>
      <td class="amount-column"><strong>-{{ number_format($salary->deductions, 2) }}</strong></td>
    </tr>

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
        <th>Description</th>
        <th>Value</th>
        <th>Description</th>
        <th>Value</th>
      </tr>
      <tr>
        <td>Working Days</td>
        <td>{{ $attendanceSummary['total_working_days'] ?? 0 }}</td>
        <td>Present Days</td>
        <td>{{ $attendanceSummary['present_days'] ?? 0 }}</td>
      </tr>
      <tr>
        <td>Absent Days</td>
        <td>{{ $attendanceSummary['absent_days'] ?? 0 }}</td>
        <td>Attendance Rate</td>
        <td>{{ $attendanceSummary['attendance_rate'] ?? 0 }}%</td>
      </tr>
      <tr>
        <td>Late Arrivals</td>
        <td>{{ $attendanceSummary['late_arrivals'] ?? 0 }}</td>
        <td>Early Departures</td>
        <td>{{ $attendanceSummary['early_departures'] ?? 0 }}</td>
      </tr>
      <tr>
        <td>Total Hours Worked</td>
        <td>{{ $attendanceSummary['total_hours_worked'] ?? 0 }}</td>
        <td>Overtime Hours</td>
        <td>{{ isset($salary->details) && isset(json_decode($salary->details, true)['overtime_hours']) ? json_decode($salary->details, true)['overtime_hours'] : 'N/A' }}</td>
      </tr>
    </table>
  </div>

  <!-- Leave Summary -->
  <div class="summary-section">
    <div class="summary-title">Leave Summary</div>
    <table class="summary-table">
      <tr>
        <th>Description</th>
        <th>Value</th>
        <th>Description</th>
        <th>Value</th>
      </tr>
      <tr>
        <td>Paid Leave Days</td>
        <td>{{ $leaveSummary['paid_leave_days'] ?? 0 }}</td>
        <td>Unpaid Leave Days</td>
        <td>{{ $leaveSummary['unpaid_leave_days'] ?? 0 }}</td>
      </tr>
      <tr>
        <td>Sick Leave Days</td>
        <td>{{ $leaveSummary['sick_leave_days'] ?? 0 }}</td>
        <td>Vacation Leave Days</td>
        <td>{{ $leaveSummary['vacation_leave_days'] ?? 0 }}</td>
      </tr>
      <tr>
        <td>Total Leave Days</td>
        <td>{{ ($leaveSummary['paid_leave_days'] ?? 0) + ($leaveSummary['unpaid_leave_days'] ?? 0) }}</td>
        <td>Other Leave Days</td>
        <td>{{ $leaveSummary['other_leave_days'] ?? 0 }}</td>
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
    <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
  </div>
</div>
</body>
</html>
