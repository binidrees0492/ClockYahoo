<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $tab)) }} Report</title>
    <style>
        @page {
            size: {{ $orientation === 'landscape' ? 'landscape' : 'portrait' }};
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        h1 { font-size: 20px; margin-bottom: 5px; color: #005a9c; }
        .meta { font-size: 11px; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body onload="window.print()">
<h1>{{ ucwords(preg_replace('/(?<!\ )[A-Z]/', ' $0', $tab)) }} Report</h1>
<div class="meta">
    Date Range: {{ $startDate ?? 'N/A' }} to {{ $endDate ?? 'N/A' }} | Generated: {{ now()->format('Y-m-d H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th>Employee</th>
        <th>Job / Assignment</th>
        <th>Task</th>
        <th>Clock In</th>
        <th>Clock Out</th>
        <th>Net Hours</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @forelse($logs as $log)
        @php
            $hours = number_format(($log->duration_minutes ?: ceil($log->netSeconds() / 60)) / 60, 2);
        @endphp
        <tr>
            <td>{{ $log->employee->name ?? 'Unknown' }}</td>
            <td>{{ $log->assignment->name ?? 'None' }}</td>
            <td>{{ $log->task->name ?? 'None' }}</td>
            <td>{{ $log->clock_in?->format('Y-m-d H:i') }}</td>
            <td>{{ $log->clock_out?->format('Y-m-d H:i') ?? '—' }}</td>
            <td>{{ $hours }}</td>
            <td>{{ ucfirst($log->status) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="7" style="text-align: center; color: #999;">No records found for this date range.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
