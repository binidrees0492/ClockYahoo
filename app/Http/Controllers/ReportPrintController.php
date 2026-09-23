<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeLog;
use Carbon\Carbon;

class ReportPrintController extends Controller
{
    public function generatePdf(Request $request)
    {
        $tab = $request->query('tab', 'QuickSummary');
        $orientation = $request->query('orientation', 'portrait');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $logs = TimeLog::with(['employee', 'assignment', 'task'])
            ->whereNotNull('clock_out')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                $q->whereBetween('clock_in', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            })
            ->get();

        return view('reports.print-template', compact('tab', 'orientation', 'startDate', 'endDate', 'logs'));
    }
}
