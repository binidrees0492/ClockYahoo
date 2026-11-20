<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        @page {
            size: {{ ($orientation ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait' }};
            margin: 12mm 15mm 15mm 15mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.35;
        }

        /* Screen Toolbar */
        .screen-toolbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .screen-toolbar button {
            background: #005a9c;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            margin-right: 8px;
        }
        .screen-toolbar button.btn-secondary {
            background: #475569;
        }
        .screen-toolbar button:hover {
            opacity: 0.9;
        }

        .report-page-container {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 40px 48px;
            box-shadow: 0 0 15px rgba(0,0,0,0.08);
            min-height: 1100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        /* Report Header */
        .report-header {
            margin-bottom: 24px;
        }
        .company-name {
            font-size: 13px;
            font-weight: 700;
            color: #000;
            margin-bottom: 12px;
        }
        .report-title {
            font-size: 22px;
            font-weight: 700;
            color: #000;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }
        .report-meta {
            font-size: 11px;
            color: #000;
            line-height: 1.5;
        }
        .meta-row {
            display: flex;
            gap: 6px;
        }
        .meta-label {
            font-weight: 700;
            min-width: 85px;
        }

        /* Section Headings */
        .section-heading {
            font-size: 16px;
            font-weight: 700;
            color: #000;
            margin-top: 18px;
            margin-bottom: 12px;
        }
        .section-subheading {
            font-size: 13px;
            font-weight: 700;
            color: #000;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        /* Tables */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 6px;
            margin-bottom: 14px;
        }
        table.report-table th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 5px 6px;
            text-align: left;
            font-weight: 700;
            color: #000;
            vertical-align: bottom;
        }
        table.report-table th.text-right,
        table.report-table td.text-right {
            text-align: right;
        }
        table.report-table th.text-center,
        table.report-table td.text-center {
            text-align: center;
        }
        table.report-table td {
            padding: 4px 6px;
            vertical-align: top;
            color: #000;
        }

        .border-top-black {
            border-top: 1.5px solid #000 !important;
        }
        .border-bottom-black {
            border-bottom: 1.5px solid #000 !important;
        }
        .border-top-thin {
            border-top: 1px solid #000 !important;
        }
        .border-bottom-thin {
            border-bottom: 1px solid #000 !important;
        }

        .font-bold {
            font-weight: 700 !important;
        }

        /* Right Summary Box */
        .summary-box-right {
            margin-left: auto;
            width: 320px;
            font-size: 10.5px;
            line-height: 1.6;
            margin-top: 14px;
            margin-bottom: 14px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 1px 0;
        }
        .summary-row.bold {
            font-weight: 700;
        }

        /* Banner Bar (Quick Summary) */
        .banner-bar {
            background-color: #e5e7eb;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 16px;
            margin-bottom: 10px;
        }

        /* Signature block */
        .signature-block {
            margin-top: 36px;
            width: 340px;
            margin-left: auto;
        }
        .sig-x {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .sig-line {
            border-bottom: 1.5px solid #000;
            height: 1px;
            margin-bottom: 6px;
        }
        .sig-name {
            text-align: right;
            font-size: 11px;
            font-weight: 400;
        }

        /* Report Footer */
        .report-footer {
            margin-top: auto;
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9.5px;
            color: #888;
        }
        .powered-by {
            font-style: italic;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: none;
            }
            .report-page-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                min-height: auto !important;
            }
            .page-break {
                page-break-after: always;
                break-after: page;
            }
        }
    </style>
</head>
<body>

{{-- Screen Toolbar for Print & Download --}}
<div class="screen-toolbar no-print">
    <div>
        <strong>{{ $title }}</strong> &mdash; {{ $company_name }}
    </div>
    <div>
        <button onclick="window.print()">&#128438; Print / Save as PDF</button>
        <button class="btn-secondary" onclick="window.close()">Close</button>
    </div>
</div>

@php
    $pageCounter = 1;
@endphp

{{-- ========================================================================= --}}
{{-- 1. JOB SUMMARY REPORT (PDF 1) & EMPLOYEE SUMMARY REPORT                   --}}
{{-- ========================================================================= --}}
@if($tab === 'JobSummary' || $tab === 'EmployeeSummary')
    @php
        $empList = $employees ?? [];
        $empChunks = array_chunk($empList, 2, true); // 2 employees per page
        if (empty($empChunks)) $empChunks = [[]];
    @endphp

    @foreach($empChunks as $chunkIndex => $chunkEmployees)
        <div class="report-page-container {{ !$loop->last ? 'page-break' : '' }}">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>

                @forelse($chunkEmployees as $emp)
                    <div class="section-heading">{{ $emp['employee_name'] }}</div>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width: 28%;">
                                    Customer:<br>Job Name
                                </th>
                                @foreach($days as $d)
                                    <th class="text-center" style="width: 9%;">
                                        {{ $d['day_name'] }}<br>{{ $d['day_date'] }}
                                    </th>
                                @endforeach
                                <th class="text-right" style="width: 9%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($emp['jobs'] as $job)
                                <tr>
                                    <td>{{ $job['job_name'] }}</td>
                                    @foreach($days as $d)
                                        <td class="text-center">
                                            {{ \App\Services\ReportDataService::formatMinutes($job['days'][$d['date_str']] ?? 0, true) }}
                                        </td>
                                    @endforeach
                                    <td class="text-right font-bold">
                                        {{ \App\Services\ReportDataService::formatMinutes($job['total_minutes'] ?? 0, false) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($days) + 2 }}" class="text-center" style="color: #888;">No job entries logged.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-top-black">
                                <td></td>
                                @foreach($days as $d)
                                    <td class="text-center font-bold">
                                        {{ \App\Services\ReportDataService::formatMinutes($emp['day_totals'][$d['date_str']] ?? 0, true) }}
                                    </td>
                                @endforeach
                                <td class="text-right font-bold">
                                    {{ \App\Services\ReportDataService::formatMinutes($emp['total_minutes'] ?? 0, false) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                @empty
                    <div style="text-align: center; color: #888; padding: 40px;">No records found for the selected date range.</div>
                @endforelse
            </div>

            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page {{ $pageCounter++ }}</span>
            </div>
        </div>
    @endforeach

{{-- ========================================================================= --}}
{{-- 2. JOB DETAIL REPORT (PDF 2) & EMPLOYEE DETAIL REPORT                     --}}
{{-- ========================================================================= --}}
@elseif($tab === 'JobDetail' || $tab === 'EmployeeDetail')
    @php
        $jobList = $jobs ?? [];
    @endphp

    @forelse($jobList as $jName => $jData)
        <div class="report-page-container {{ !$loop->last ? 'page-break' : '' }}">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>

                <div class="section-heading">{{ $jData['job_name'] }}</div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 13%;">Date</th>
                            <th style="width: 17%;">Employee</th>
                            <th style="width: 12%;">Task</th>
                            <th class="text-center" style="width: 9%;">In</th>
                            <th class="text-center" style="width: 11%;">Start Break</th>
                            <th class="text-center" style="width: 11%;">End Break</th>
                            <th class="text-center" style="width: 9%;">Break Total</th>
                            <th class="text-center" style="width: 9%;">Out</th>
                            <th class="text-right" style="width: 9%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jData['dates'] as $dKey => $dayData)
                            @foreach($dayData['entries'] as $eIndex => $entry)
                                @php
                                    $breaks = $entry['breaks'];
                                    $firstBreak = $breaks[0] ?? null;
                                    $extraBreaks = array_slice($breaks, 1);
                                @endphp
                                <tr>
                                    <td>
                                        @if($eIndex === 0)
                                            {{ $dayData['day_of_week'] }}<br>{{ $dayData['date_formatted'] }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $parts = explode(', ', $entry['employee_name']);
                                        @endphp
                                        {{ $parts[0] ?? '' }}@if(isset($parts[1])),<br>{{ $parts[1] }}@endif
                                    </td>
                                    <td>{{ $entry['task_name'] }}</td>
                                    <td class="text-center">{{ $entry['in_formatted'] }}</td>
                                    <td class="text-center">{{ $firstBreak ? $firstBreak['start_formatted'] : '—' }}</td>
                                    <td class="text-center">{{ $firstBreak ? $firstBreak['end_formatted'] : '—' }}</td>
                                    <td class="text-center">{{ $firstBreak ? $firstBreak['total_formatted'] : '—' }}</td>
                                    <td class="text-center">{{ $entry['out_formatted'] }}</td>
                                    <td class="text-right font-bold">{{ $entry['total_formatted'] }}</td>
                                </tr>

                                {{-- Additional breaks on sub-rows --}}
                                @foreach($extraBreaks as $xb)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-center">{{ $xb['start_formatted'] }}</td>
                                        <td class="text-center">{{ $xb['end_formatted'] }}</td>
                                        <td class="text-center">{{ $xb['total_formatted'] }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            @endforeach

                            {{-- Daily Total Row --}}
                            <tr>
                                <td colspan="7"></td>
                                <td class="text-right font-bold border-top-thin">Daily Total</td>
                                <td class="text-right font-bold border-top-thin">
                                    {{ \App\Services\ReportDataService::formatMinutes($dayData['daily_total_min'], false) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-top-black">
                            <td colspan="7"></td>
                            <td class="text-right font-bold">Grand Total</td>
                            <td class="text-right font-bold">
                                {{ \App\Services\ReportDataService::formatMinutes($jData['grand_total_min'], false) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Summaries at bottom --}}
                <div style="margin-top: 24px; max-width: 420px; margin-left: auto;">
                    {{-- Employee Total Box --}}
                    <div style="font-weight: 700; margin-bottom: 4px;">Employee Total</div>
                    <div class="summary-row bold" style="border-bottom: 1.5px solid #000; padding-bottom: 3px; margin-bottom: 4px;">
                        <span>Employee</span>
                        <span>Total</span>
                    </div>
                    @foreach($jData['emp_totals'] as $empN => $empMin)
                        <div class="summary-row">
                            <span>{{ $empN }}</span>
                            <span>{{ \App\Services\ReportDataService::formatMinutes($empMin, false) }}</span>
                        </div>
                    @endforeach
                    <div class="summary-row bold" style="border-top: 1.5px solid #000; padding-top: 3px; margin-top: 4px; margin-bottom: 16px;">
                        <span>Total</span>
                        <span>{{ \App\Services\ReportDataService::formatMinutes($jData['grand_total_min'], false) }}</span>
                    </div>

                    {{-- Task Total Box --}}
                    <div style="font-weight: 700; margin-bottom: 4px;">Task Total</div>
                    <div class="summary-row bold" style="border-bottom: 1.5px solid #000; padding-bottom: 3px; margin-bottom: 4px;">
                        <span>Task</span>
                        <span>Total</span>
                    </div>
                    @foreach($jData['task_totals'] as $taskN => $taskMin)
                        <div class="summary-row">
                            <span>{{ $taskN }}</span>
                            <span>{{ \App\Services\ReportDataService::formatMinutes($taskMin, false) }}</span>
                        </div>
                    @endforeach
                    <div class="summary-row bold" style="border-top: 1.5px solid #000; padding-top: 3px; margin-top: 4px;">
                        <span>Total</span>
                        <span>{{ \App\Services\ReportDataService::formatMinutes($jData['grand_total_min'], false) }}</span>
                    </div>
                </div>
            </div>

            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page {{ $pageCounter++ }}</span>
            </div>
        </div>
    @empty
        <div class="report-page-container">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>
                <div style="text-align: center; color: #888; padding: 40px;">No records found for the selected date range.</div>
            </div>
            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page 1</span>
            </div>
        </div>
    @endforelse

{{-- ========================================================================= --}}
{{-- 3. QUICK SUMMARY REPORT OF EMPLOYEES (PDF 3)                             --}}
{{-- ========================================================================= --}}
@elseif($tab === 'QuickSummary')
    <div class="report-page-container">
        <div>
            <div class="report-header">
                <div class="company-name">{{ $company_name }}</div>
                <div class="report-title">{{ $title }}</div>
                <div class="report-meta">
                    <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                    <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                </div>
            </div>

            {{-- SUMMARY SECTION --}}
            <div class="banner-bar">SUMMARY</div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Employee</th>
                        <th class="text-right" style="width: 10%;">Reg</th>
                        <th class="text-right" style="width: 10%;">OT</th>
                        <th class="text-right" style="width: 10%;">DT</th>
                        <th class="text-right" style="width: 11%;">Paid Off</th>
                        <th class="text-right" style="width: 12%;">Unpaid Off</th>
                        <th class="text-right" style="width: 12%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td>{{ $emp['name'] }}</td>
                            <td class="text-right">{{ \App\Services\ReportDataService::formatMinutes($emp['reg_min'], false) }}</td>
                            <td class="text-right">{{ $emp['ot_min'] > 0 ? \App\Services\ReportDataService::formatMinutes($emp['ot_min'], false) : '-' }}</td>
                            <td class="text-right">{{ $emp['dt_min'] > 0 ? \App\Services\ReportDataService::formatMinutes($emp['dt_min'], false) : '-' }}</td>
                            <td class="text-right">-</td>
                            <td class="text-right">-</td>
                            <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['total_min'], false) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="color: #888;">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-top-black border-bottom-black">
                        <td class="font-bold">Grand Total</td>
                        <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($grand_total_min ?? 0, false) }}</td>
                        <td class="text-right font-bold">0:00</td>
                        <td class="text-right font-bold">0:00</td>
                        <td class="text-right font-bold">0:00</td>
                        <td class="text-right font-bold">0:00</td>
                        <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($grand_total_min ?? 0, false) }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- BREAKDOWN SECTION --}}
            <div class="banner-bar" style="margin-top: 30px;">BREAKDOWN</div>

            @foreach($employees as $emp)
                <div style="margin-bottom: 24px;">
                    <div class="section-heading" style="margin-bottom: 6px;">{{ $emp['name'] }}</div>
                    <div style="border-bottom: 1.5px solid #000; margin-bottom: 10px;"></div>

                    <div style="display: flex; gap: 30px;">
                        {{-- Time Off table --}}
                        <div style="flex: 1;">
                            <table class="report-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th>Time Off</th>
                                        <th class="text-right">Paid</th>
                                        <th class="text-right">Unpaid</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Empty row for standard appearance --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Work table --}}
                        <div style="flex: 1.3;">
                            <table class="report-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th>Work</th>
                                        <th class="text-right">Reg</th>
                                        <th class="text-right">OT</th>
                                        <th class="text-right">DT</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['total_min'], false) }}</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right">-</td>
                                        <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['total_min'], false) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="font-bold" style="padding-top: 8px;">Jobs</td>
                                    </tr>
                                    @foreach($emp['jobs'] as $jName => $jMin)
                                        <tr>
                                            <td style="padding-left: 12px;">{{ $jName }}</td>
                                            <td class="text-right">{{ \App\Services\ReportDataService::formatMinutes($jMin, false) }}</td>
                                            <td class="text-right">-</td>
                                            <td class="text-right">-</td>
                                            <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($jMin, false) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="5" class="font-bold" style="padding-top: 8px;">Tasks</td>
                                    </tr>
                                    @foreach($emp['tasks'] as $tName => $tMin)
                                        <tr>
                                            <td style="padding-left: 12px;">{{ $tName }}</td>
                                            <td class="text-right">{{ \App\Services\ReportDataService::formatMinutes($tMin, false) }}</td>
                                            <td class="text-right">-</td>
                                            <td class="text-right">-</td>
                                            <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($tMin, false) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="report-footer">
            <span class="powered-by">Powered by ClockShark</span>
            <span>Page 1</span>
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 4. EMPLOYEE PAY RATE REPORT (PDF 4)                                      --}}
{{-- ========================================================================= --}}
@elseif($tab === 'PayRate')
    @php
        $empList = $employees ?? [];
        $totalEmpCount = count($empList);
        $empIndex = 0;
    @endphp

    @forelse($empList as $emp)
        @php
            $empIndex++;
            $isLastEmp = ($empIndex === $totalEmpCount);
        @endphp
        <div class="report-page-container {{ !$isLastEmp ? 'page-break' : '' }}">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>

                <div class="section-heading">{{ $emp['name'] }}</div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 25%; vertical-align: top;">Customer: Job</th>
                            <th rowspan="2" style="width: 13%; vertical-align: top;">Task</th>
                            <th colspan="3" class="text-center" style="border-bottom: 1px solid #000;">Regular ($)</th>
                            <th colspan="3" class="text-center" style="border-bottom: 1px solid #000;">Overtime x1.5 ($)</th>
                            <th colspan="3" class="text-center" style="border-bottom: 1px solid #000;">Double Time x2 ($)</th>
                            <th rowspan="2" class="text-right" style="width: 10%; vertical-align: top;">Total<br>Cost</th>
                        </tr>
                        <tr>
                            <th class="text-right" style="width: 6%;">Rate</th>
                            <th class="text-right" style="width: 7%;">Hrs</th>
                            <th class="text-right" style="width: 7%;">Cost</th>
                            <th class="text-right" style="width: 6%;">Rate</th>
                            <th class="text-right" style="width: 7%;">Hrs</th>
                            <th class="text-right" style="width: 7%;">Cost</th>
                            <th class="text-right" style="width: 6%;">Rate</th>
                            <th class="text-right" style="width: 7%;">Hrs</th>
                            <th class="text-right" style="width: 7%;">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emp['rows'] as $r)
                            <tr>
                                <td>{{ $r['job_name'] }}</td>
                                <td>{{ $r['task_name'] }}</td>
                                <td class="text-right">{{ number_format($r['rate'], 2) }}</td>
                                <td class="text-right">{{ \App\Services\ReportDataService::formatMinutes($r['reg_min'], false) }}</td>
                                <td class="text-right">{{ number_format($r['reg_cost'], 2) }}</td>
                                <td class="text-right">{{ number_format($r['ot_rate'], 2) }}</td>
                                <td class="text-right">{{ \App\Services\ReportDataService::formatMinutes($r['ot_min'], false) }}</td>
                                <td class="text-right">{{ number_format($r['ot_cost'], 2) }}</td>
                                <td class="text-right">{{ number_format($r['dt_rate'], 2) }}</td>
                                <td class="text-right">{{ \App\Services\ReportDataService::formatMinutes($r['dt_min'], false) }}</td>
                                <td class="text-right">{{ number_format($r['dt_cost'], 2) }}</td>
                                <td class="text-right font-bold">{{ number_format($r['total_cost'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-top-black">
                            <td class="font-bold">Total Hours</td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['total_min'], false) }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-bold">0:00</td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-bold">0:00</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Right Summary Box --}}
                <div class="summary-box-right">
                    <div style="font-weight: 700; margin-bottom: 4px;">Job Totals for Employee</div>
                    @foreach($emp['job_totals'] as $jTitle => $jCost)
                        <div class="summary-row">
                            <span style="max-width: 220px; text-align: left;">{{ $jTitle }} Total Cost</span>
                            <span class="font-bold">{{ number_format($jCost, 2) }}</span>
                        </div>
                    @endforeach

                    <div style="font-weight: 700; margin-top: 14px; margin-bottom: 4px;">Task Totals for Employee</div>
                    @foreach($emp['task_totals'] as $tTitle => $tCost)
                        <div class="summary-row">
                            <span style="max-width: 220px; text-align: left;">{{ $tTitle }} Total Cost</span>
                            <span class="font-bold">{{ number_format($tCost, 2) }}</span>
                        </div>
                    @endforeach

                    <div class="summary-row bold" style="margin-top: 16px; font-size: 11px;">
                        <span>Employee Grand Total Cost</span>
                        <span>{{ number_format($emp['total_cost'], 2) }}</span>
                    </div>

                    @if($isLastEmp)
                        <div class="summary-row bold" style="margin-top: 24px; font-size: 13px;">
                            <span>Company Total Cost</span>
                            <span>{{ number_format($company_total_cost ?? 0, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page {{ $pageCounter++ }}</span>
            </div>
        </div>
    @empty
        <div class="report-page-container">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>
                <div style="text-align: center; color: #888; padding: 40px;">No records found for the selected date range.</div>
            </div>
            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page 1</span>
            </div>
        </div>
    @endforelse

{{-- ========================================================================= --}}
{{-- 5. EMPLOYEE TIMESHEET (PDF 5)                                            --}}
{{-- ========================================================================= --}}
@elseif($tab === 'TimeSheet')
    @php
        $empList = $employees ?? [];
        $totalEmpCount = count($empList);
        $empIdx = 0;
    @endphp

    @forelse($empList as $emp)
        @php
            $empIdx++;
            $totalWeeks = count($emp['weeks']);
            $wCounter = 0;
        @endphp

        @foreach($emp['weeks'] as $wIndex => $wData)
            @php
                $wCounter++;
                $isLastWeekOfEmp = ($wCounter === $totalWeeks);
                $isLastPageOfReport = ($empIdx === $totalEmpCount && $isLastWeekOfEmp);
            @endphp
            <div class="report-page-container {{ !$isLastPageOfReport ? 'page-break' : '' }}">
                <div>
                    <div class="report-header">
                        <div class="company-name">{{ $company_name }}</div>
                        <div class="report-title">{{ $title }}</div>
                        <div class="report-meta">
                            <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                            <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                        </div>
                    </div>

                    <div class="section-heading">{{ $emp['name'] }}</div>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Customer:<br>Job</th>
                                <th style="width: 13%;">Task</th>
                                @foreach($wData['days'] as $d)
                                    <th class="text-center" style="width: 8%;">
                                        {{ $d['day_name'] }}<br>{{ $d['day_date'] }}
                                    </th>
                                @endforeach
                                <th class="text-right" style="width: 9%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($wData['jobs'] as $job)
                                <tr>
                                    <td>{{ $job['job_name'] }}</td>
                                    <td>{{ $job['task_name'] }}</td>
                                    @foreach($wData['days'] as $d)
                                        <td class="text-center">
                                            {{ \App\Services\ReportDataService::formatMinutes($job['days'][$d['date_str']] ?? 0, true) }}
                                        </td>
                                    @endforeach
                                    <td class="text-right font-bold">
                                        {{ \App\Services\ReportDataService::formatMinutes($job['total_minutes'] ?? 0, false) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td>—</td>
                                    <td>—</td>
                                    @foreach($wData['days'] as $d)
                                        <td class="text-center">—</td>
                                    @endforeach
                                    <td class="text-right">—</td>
                                </tr>
                            @endforelse

                            {{-- Summary rows under jobs --}}
                            <tr class="border-top-black">
                                <td class="font-bold">Regular</td>
                                <td></td>
                                @foreach($wData['days'] as $d)
                                    <td class="text-center font-bold">
                                        {{ \App\Services\ReportDataService::formatMinutes($wData['day_totals'][$d['date_str']] ?? 0, true) }}
                                    </td>
                                @endforeach
                                <td class="text-right font-bold">
                                    {{ \App\Services\ReportDataService::formatMinutes($wData['week_total'], false) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Overtime</td>
                                <td colspan="{{ count($wData['days']) + 2 }}"></td>
                            </tr>
                            <tr>
                                <td>Doubletime</td>
                                <td colspan="{{ count($wData['days']) + 2 }}"></td>
                            </tr>
                            <tr>
                                <td>Time Off</td>
                                <td colspan="{{ count($wData['days']) + 2 }}"></td>
                            </tr>
                            <tr>
                                <td>Holiday</td>
                                <td colspan="{{ count($wData['days']) + 2 }}"></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-top-black border-bottom-black">
                                <td class="font-bold">Total</td>
                                <td></td>
                                @foreach($wData['days'] as $d)
                                    <td class="text-center font-bold">
                                        {{ \App\Services\ReportDataService::formatMinutes($wData['day_totals'][$d['date_str']] ?? 0, true) }}
                                    </td>
                                @endforeach
                                <td class="text-right font-bold">
                                    {{ \App\Services\ReportDataService::formatMinutes($wData['week_total'], false) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    {{-- Right-aligned summary box --}}
                    <div class="summary-box-right">
                        <div class="summary-row">
                            <span>Week Total Regular</span>
                            <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($wData['week_total'], false) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Week Total Overtime</span>
                            <span>0:00</span>
                        </div>

                        @if($isLastWeekOfEmp)
                            <div class="summary-row" style="margin-top: 4px;">
                                <span>Grand Total Regular</span>
                                <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['grand_total_min'], false) }}</span>
                            </div>
                            <div class="summary-row">
                                <span>Grand Total Overtime</span>
                                <span>0:00</span>
                            </div>

                            {{-- Signature Block --}}
                            <div class="signature-block">
                                <div class="sig-x">x</div>
                                <div class="sig-line"></div>
                                <div class="sig-name">{{ $emp['name'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="report-footer">
                    <span class="powered-by">Powered by ClockShark</span>
                    <span>Page {{ $pageCounter++ }}</span>
                </div>
            </div>
        @endforeach
    @empty
        <div class="report-page-container">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>
                <div style="text-align: center; color: #888; padding: 40px;">No records found for the selected date range.</div>
            </div>
            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page 1</span>
            </div>
        </div>
    @endforelse

{{-- ========================================================================= --}}
{{-- 6. TASK SUMMARY REPORT (PDF 6)                                            --}}
{{-- ========================================================================= --}}
@elseif($tab === 'TaskSummary')
    <div class="report-page-container">
        <div>
            <div class="report-header">
                <div class="company-name">{{ $company_name }}</div>
                <div class="report-title">{{ $title }}</div>
                <div class="report-meta">
                    <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                    <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                </div>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 28%; vertical-align: bottom;">Name</th>
                        @foreach($days as $d)
                            <th class="text-center" style="width: 9%;">
                                {{ $d['day_name'] }}<br>{{ $d['day_date'] }}
                            </th>
                        @endforeach
                        <th class="text-right" style="width: 9%; vertical-align: bottom;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks ?? [] as $t)
                        <tr>
                            <td>{{ $t['task_name'] }}</td>
                            @foreach($days as $d)
                                <td class="text-center">
                                    {{ \App\Services\ReportDataService::formatMinutes($t['days'][$d['date_str']] ?? 0, true) }}
                                </td>
                            @endforeach
                            <td class="text-right font-bold">
                                {{ \App\Services\ReportDataService::formatMinutes($t['total_minutes'] ?? 0, false) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($days ?? []) + 2 }}" class="text-center" style="color: #888; padding: 20px;">No task entries logged.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-top-black">
                        <td></td>
                        @foreach($days ?? [] as $d)
                            <td class="text-center font-bold">
                                {{ \App\Services\ReportDataService::formatMinutes($day_totals[$d['date_str']] ?? 0, true) }}
                            </td>
                        @endforeach
                        <td class="text-right font-bold">
                            {{ \App\Services\ReportDataService::formatMinutes($grand_total ?? 0, false) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="report-footer">
            <span class="powered-by">Powered by ClockShark</span>
            <span>Page {{ $pageCounter++ }}</span>
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 7. TASK DETAIL REPORT (PDF 7)                                             --}}
{{-- ========================================================================= --}}
@elseif($tab === 'TaskDetail')
    <div class="report-page-container">
        <div>
            <div class="report-header">
                <div class="company-name">{{ $company_name }}</div>
                <div class="report-title">{{ $title }}</div>
                <div class="report-meta">
                    <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                    <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                </div>
            </div>

            @forelse($tasks ?? [] as $t)
                <div class="section-heading" style="font-size: 15px; margin-top: 14px; margin-bottom: 8px;">{{ $t['task_name'] }}</div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 18%;">Employee</th>
                            <th style="width: 24%;">Customer: Job</th>
                            <th class="text-center" style="width: 8%;">In</th>
                            <th class="text-center" style="width: 8%;">Start Break</th>
                            <th class="text-center" style="width: 8%;">End Break</th>
                            <th class="text-center" style="width: 8%;">Break Total</th>
                            <th class="text-center" style="width: 8%;">Out</th>
                            <th class="text-right" style="width: 8%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($t['dates'] as $dateData)
                            @foreach($dateData['shifts'] as $shift)
                                <tr>
                                    <td>{{ $dateData['display_date'] }}</td>
                                    <td>{{ $shift['employee_name'] }}</td>
                                    <td>{{ $shift['job_name'] }}</td>
                                    <td class="text-center">{{ $shift['clock_in'] }}</td>
                                    <td class="text-center">{{ $shift['first_break']['start_formatted'] ?? '—' }}</td>
                                    <td class="text-center">{{ $shift['first_break']['end_formatted'] ?? '—' }}</td>
                                    <td class="text-center">{{ $shift['first_break']['total_formatted'] ?? '—' }}</td>
                                    <td class="text-center">{{ $shift['clock_out'] }}</td>
                                    <td class="text-right font-bold">{{ $shift['total_fmt'] }}</td>
                                </tr>
                                @foreach($shift['extra_breaks'] as $eb)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-center"></td>
                                        <td class="text-center">{{ $eb['start_formatted'] }}</td>
                                        <td class="text-center">{{ $eb['end_formatted'] }}</td>
                                        <td class="text-center">{{ $eb['total_formatted'] }}</td>
                                        <td class="text-center"></td>
                                        <td class="text-right"></td>
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr>
                                <td colspan="8" class="text-right font-bold" style="border-top: 1px solid #000; padding: 4px 6px;">Daily Total</td>
                                <td class="text-right font-bold" style="border-top: 1px solid #000; padding: 4px 6px;">
                                    {{ \App\Services\ReportDataService::formatMinutes($dateData['daily_total'], false) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="text-align: right; font-weight: 700; font-size: 13px; margin: 10px 0 20px 0;">
                    Grand Total &nbsp;&nbsp;&nbsp;&nbsp; {{ \App\Services\ReportDataService::formatMinutes($t['grand_total_min'], false) }}
                </div>
            @empty
                <div style="text-align: center; color: #888; padding: 40px;">No records found for the selected date range.</div>
            @endforelse

            @if(!empty($grand_employee_totals) || !empty($grand_job_totals))
                <div style="display: flex; gap: 40px; margin-top: 28px; page-break-inside: avoid;">
                    {{-- Employee Total Box --}}
                    @if(!empty($grand_employee_totals))
                        <div style="flex: 1; max-width: 320px;">
                            <table class="report-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th colspan="2" style="font-size: 13px; border-bottom: 1.5px solid #000;">Employee Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grand_employee_totals as $empName => $empMins)
                                        <tr>
                                            <td style="padding: 2px 4px;">{{ $empName }}</td>
                                            <td class="text-right font-bold" style="padding: 2px 4px;">
                                                {{ \App\Services\ReportDataService::formatMinutes($empMins, false) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-top-black">
                                        <td class="font-bold" style="padding: 4px;">Total</td>
                                        <td class="text-right font-bold" style="padding: 4px;">
                                            {{ \App\Services\ReportDataService::formatMinutes($overall_grand_total, false) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif

                    {{-- Customer: Job Total Box --}}
                    @if(!empty($grand_job_totals))
                        <div style="flex: 1; max-width: 380px;">
                            <table class="report-table" style="margin-top: 0;">
                                <thead>
                                    <tr>
                                        <th colspan="2" style="font-size: 13px; border-bottom: 1.5px solid #000;">Customer: Job Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grand_job_totals as $jName => $jMins)
                                        <tr>
                                            <td style="padding: 2px 4px;">{{ $jName }}</td>
                                            <td class="text-right font-bold" style="padding: 2px 4px;">
                                                {{ \App\Services\ReportDataService::formatMinutes($jMins, false) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-top-black">
                                        <td class="font-bold" style="padding: 4px;">Total</td>
                                        <td class="text-right font-bold" style="padding: 4px;">
                                            {{ \App\Services\ReportDataService::formatMinutes($overall_grand_total, false) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="report-footer">
            <span class="powered-by">Powered by ClockShark</span>
            <span>Page {{ $pageCounter++ }}</span>
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 8. TIME OFF REPORT (PTO)                                                  --}}
{{-- ========================================================================= --}}
@elseif($tab === 'Pto')
    @forelse($employees ?? [] as $emp)
        <div class="report-page-container {{ !$loop->last ? 'page-break' : '' }}">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>

                <div class="section-heading" style="font-size: 17px; margin-bottom: 12px;">{{ $emp['employee_name'] }}</div>

                {{-- SUMMARY --}}
                <div class="banner-bar">SUMMARY</div>
                <div style="font-size: 11px; margin-bottom: 12px; color: #000;">{{ $summary_range_text ?? $date_range_text }}</div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                    <div style="font-size: 11px; font-weight: 400; width: 22%;">
                        {{ $emp['requests_count'] }} Requests
                    </div>

                    <div style="width: 44%;">
                        <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px;">Paid Time Off</div>
                        @foreach($emp['policies'] as $pName => $pMins)
                            <div style="display: flex; justify-content: space-between; font-size: 11px; padding: 1px 0; max-width: 240px;">
                                <span>{{ $pName }}</span>
                                <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($pMins, false) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div style="width: 26%; font-size: 11px;">
                        <div style="display: flex; justify-content: space-between; padding: 1px 0;">
                            <span>Requested</span>
                            <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['requested_min'], false) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1px 0;">
                            <span>Declined</span>
                            <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['declined_min'], false) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1px 0;">
                            <span>Pending</span>
                            <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['pending_min'], false) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1px 0;">
                            <span>Approved</span>
                            <span class="font-bold">{{ \App\Services\ReportDataService::formatMinutes($emp['approved_min'], false) }}</span>
                        </div>
                    </div>
                </div>

                {{-- DETAILS --}}
                <div class="banner-bar">DETAILS</div>

                @forelse($emp['requests'] as $req)
                    <table class="report-table" style="margin-bottom: 6px;">
                        <thead>
                            <tr>
                                <th style="width: 28%;">Policy</th>
                                <th style="width: 24%;">Date</th>
                                <th class="text-center" style="width: 16%;">Start</th>
                                <th class="text-center" style="width: 16%;">End</th>
                                <th class="text-right" style="width: 16%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($req['days'] as $dIdx => $day)
                                <tr>
                                    <td>{{ $dIdx === 0 ? $req['policy_name'] : '' }}</td>
                                    <td>{{ $day['date_formatted'] }}</td>
                                    <td class="text-center">{{ $day['start_formatted'] }}</td>
                                    <td class="text-center">{{ $day['end_formatted'] }}</td>
                                    <td class="text-right font-bold">{{ $day['total_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right font-bold" style="padding-top: 6px;">{{ $req['status_label'] }}</td>
                                <td class="text-right font-bold" style="padding-top: 6px;">{{ $req['total_fmt'] }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    @if(!empty($req['notes']))
                        <div style="font-size: 11px; margin-top: 8px; margin-bottom: 20px; line-height: 1.4;">
                            <span class="font-bold">Notes</span>&nbsp;&nbsp;{!! nl2br(e($req['notes'])) !!}
                        </div>
                    @endif
                @empty
                    <div style="text-align: center; color: #888; padding: 20px;">No time off records logged.</div>
                @endforelse
            </div>

            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page {{ $pageCounter++ }}</span>
            </div>
        </div>
    @empty
        <div class="report-page-container">
            <div>
                <div class="report-header">
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-meta">
                        <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                        <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                    </div>
                </div>
                <div style="text-align: center; color: #888; padding: 40px;">No time off requests found for the selected date range.</div>
            </div>
            <div class="report-footer">
                <span class="powered-by">Powered by ClockShark</span>
                <span>Page 1</span>
            </div>
        </div>
    @endforelse

{{-- ========================================================================= --}}
{{-- 9. TIMESHEET ALERTS REPORT                                                --}}
{{-- ========================================================================= --}}
@elseif($tab === 'TimeSheetAlerts')
    <div class="report-page-container">
        <div>
            <div class="report-header">
                <div class="company-name">{{ $company_name }}</div>
                <div class="report-title">{{ $title }}</div>
                <div class="report-meta">
                    <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                    <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                </div>
            </div>

            {{-- SUMMARY --}}
            <div class="banner-bar">SUMMARY</div>
            <div style="font-size: 11px; margin-bottom: 4px; color: #000;">{{ $summary_range_text ?? $date_range_text }}</div>
            <div style="font-size: 11px; margin-bottom: 12px; color: #000;">{{ $total_alerts }} Alerts</div>

            <div style="max-width: 320px; margin-bottom: 24px;">
                <table class="report-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Alert</th>
                            <th class="text-right">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alert_counts as $aName => $aQty)
                            <tr>
                                <td>{{ $aName }}</td>
                                <td class="text-right font-bold">{{ $aQty }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-top-black">
                            <td class="font-bold">Total</td>
                            <td class="text-right font-bold">{{ $total_alerts }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- DETAILS --}}
            <div class="banner-bar">DETAILS</div>

            @forelse($employees as $emp)
                <div style="margin-top: 14px; margin-bottom: 4px;">
                    <div class="section-heading" style="margin-bottom: 2px;">{{ $emp['employee_name'] }}</div>
                    <div style="font-size: 11px; color: #444; margin-bottom: 8px;">
                        {{ $emp['alerts_count'] }} {{ implode(', ', array_keys($emp['alert_breakdown'])) }}
                    </div>
                </div>

                <table class="report-table" style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Date</th>
                            <th style="width: 24%;">Time</th>
                            <th style="width: 38%;">Customer: Job</th>
                            <th style="width: 20%;">Alerts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emp['rows'] as $r)
                            <tr>
                                <td>{{ $r['date'] }}</td>
                                <td>{{ $r['time_range'] }}</td>
                                <td>{{ $r['job_name'] }}</td>
                                <td class="font-bold">{{ $r['alerts_str'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @empty
                <div style="text-align: center; color: #888; padding: 20px;">No alerts found for this date range.</div>
            @endforelse
        </div>

        <div class="report-footer">
            <span class="powered-by">Powered by ClockShark</span>
            <span>Page {{ $pageCounter++ }}</span>
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 10. PAYROLL REPORT 2025                                                   --}}
{{-- ========================================================================= --}}
@elseif($tab === 'PayrollReport')
    <div class="report-page-container">
        <div>
            <div class="report-header">
                <div class="company-name">{{ $company_name }}</div>
                <div class="report-title">Payroll Report 2025</div>
                <div class="report-meta">
                    <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                    <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                </div>
            </div>

            <table class="report-table" style="font-size: 10px;">
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Payable To</th>
                        <th class="text-right">Regular (Mins)</th>
                        <th class="text-right">Overtime (Mins)</th>
                        <th class="text-right">Sick (Hrs)</th>
                        <th class="text-right">Vacation (Hrs)</th>
                        <th class="text-right">Total Hours</th>
                        <th class="text-right">2025 Pay Rate</th>
                        <th class="text-right">Total Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees ?? [] as $emp)
                        @php
                            $totHours = number_format(($emp['reg_minutes'] + $emp['ot_minutes']) / 60 + $emp['sick_hours'] + $emp['vacation_hours'], 2);
                        @endphp
                        <tr>
                            <td>{{ $emp['first_name'] }}</td>
                            <td>{{ $emp['last_name'] }}</td>
                            <td>{{ $emp['payable_to'] }}</td>
                            <td class="text-right">{{ $emp['reg_minutes'] }}</td>
                            <td class="text-right">{{ $emp['ot_minutes'] }}</td>
                            <td class="text-right">{{ number_format($emp['sick_hours'], 2) }}</td>
                            <td class="text-right">{{ number_format($emp['vacation_hours'], 2) }}</td>
                            <td class="text-right font-bold">{{ $totHours }}</td>
                            <td class="text-right">${{ number_format($emp['rate_2025'], 2) }}/hr</td>
                            <td class="text-right font-bold">${{ number_format($emp['total_paid'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center" style="color: #888; padding: 20px;">No payroll records found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="report-footer">
            <span class="powered-by">Powered by ClockShark</span>
            <span>Page {{ $pageCounter++ }}</span>
        </div>
    </div>

{{-- ========================================================================= --}}
{{-- 11. FALLBACK GENERIC REPORT                                              --}}
{{-- ========================================================================= --}}
@else
    <div class="report-page-container">
        <div>
            <div class="report-header">
                <div class="company-name">{{ $company_name }}</div>
                <div class="report-title">{{ $title }}</div>
                <div class="report-meta">
                    <div class="meta-row"><span class="meta-label">Date Range:</span> {{ $date_range_text }}</div>
                    <div class="meta-row"><span class="meta-label">Generated:</span> {{ $generated_at_text }}</div>
                </div>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Job / Assignment</th>
                        <th>Task</th>
                        <th class="text-center">Clock In</th>
                        <th class="text-center">Clock Out</th>
                        <th class="text-right">Net Hours</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs ?? [] as $log)
                        @php
                            $minutes = (int) ($log->duration_minutes ?: ceil($log->netSeconds() / 60));
                        @endphp
                        <tr>
                            <td>{{ \App\Services\ReportDataService::formatEmployeeName($log->employee) }}</td>
                            <td>{{ \App\Services\ReportDataService::getJobLabel($log->assignment, $company_name) }}</td>
                            <td>{{ $log->task->name ?? 'General' }}</td>
                            <td class="text-center">{{ $log->clock_in?->setTimezone($timezone)->format('m/d/Y g:i A') }}</td>
                            <td class="text-center">{{ $log->clock_out?->setTimezone($timezone)->format('m/d/Y g:i A') ?? '—' }}</td>
                            <td class="text-right font-bold">{{ \App\Services\ReportDataService::formatMinutes($minutes, false) }}</td>
                            <td class="text-center">{{ ucfirst($log->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="color: #888; padding: 20px;">No records found for this date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="report-footer">
            <span class="powered-by">Powered by ClockShark</span>
            <span>Page 1</span>
        </div>
    </div>
@endif

</body>
</html>
