// File Path: app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Time Tracking Report Data
    public function timeTracking(Request $request)
    {
        $query = DB::table('tbl_time_logging as tl')
            ->join('tbl_employees as e', 'tl.employee_id', '=', 'e.id')
            ->select(
                'tl.id',
                'tl.clock_in',
                'tl.clock_out',
                'tl.total_hours',
                'e.first_name',
                'e.last_name'
            );

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tl.clock_in', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->get());
    }

    // Job & Employee Mileage Report Data
    public function jobMileage(Request $request)
    {
        $data = DB::table('tbl_time_issues as ti')
            ->join('tbl_employees as e', 'ti.employee_id', '=', 'e.id')
            ->select('ti.*', 'e.first_name', 'e.last_name')
            ->get();

        return response()->json($data);
    }

    // Financials & Payroll Report Data
    public function financials(Request $request)
    {
        $data = DB::table('tbl_payroll as p')
            ->join('tbl_employees as e', 'p.employee_id', '=', 'e.id')
            ->leftJoin('tbl_payroll_items as pi', 'p.id', '=', 'pi.payroll_id')
            ->select('p.*', 'e.first_name', 'e.last_name', 'pi.item_name', 'pi.amount')
            ->get();

        return response()->json($data);
    }
}

// End of File: app/Http/Controllers/ReportController.php